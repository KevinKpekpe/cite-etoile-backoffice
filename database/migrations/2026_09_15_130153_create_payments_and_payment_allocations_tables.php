<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_reference', 50)->unique();
            $table->foreignId('customer_id');
            $table->foreignId('subscription_id');
            $table->dateTime('payment_date')->useCurrent();
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'card', 'other']);
            $table->string('transaction_reference', 150)->nullable();
            $table->enum('status', ['pending', 'validated', 'cancelled', 'reversed'])->default('validated');
            $table->foreignId('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->index('customer_id', 'idx_payments_customer');
            $table->index('subscription_id', 'idx_payments_subscription');
            $table->index('payment_date', 'idx_payments_date');
            $table->index('status', 'idx_payments_status');
            $table->foreign('customer_id', 'fk_payments_customer')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('subscription_id', 'fk_payments_subscription')->references('id')->on('subscriptions')->restrictOnDelete();
            $table->foreign('received_by', 'fk_payments_received_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT chk_payments_amount CHECK (amount > 0)');

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id');
            $table->foreignId('installment_id');
            $table->decimal('amount', 12, 2);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->unique(['payment_id', 'installment_id'], 'uq_payment_installment');
            $table->index('payment_id', 'idx_payment_allocations_payment');
            $table->index('installment_id', 'idx_payment_allocations_installment');
            $table->foreign('payment_id', 'fk_payment_allocations_payment')->references('id')->on('payments')->restrictOnDelete();
            $table->foreign('installment_id', 'fk_payment_allocations_installment')->references('id')->on('installments')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE payment_allocations ADD CONSTRAINT chk_payment_allocations_amount CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
    }
};

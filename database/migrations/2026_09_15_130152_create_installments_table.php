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
        Schema::create('installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id');
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->storedAs('amount_due - amount_paid');
            $table->dateTime('paid_at')->nullable();
            $table->enum('status', ['upcoming', 'due', 'partially_paid', 'paid', 'overdue'])->default('upcoming');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['subscription_id', 'installment_number'], 'uq_installment_number');
            $table->index('subscription_id', 'idx_installments_subscription');
            $table->index('due_date', 'idx_installments_due_date');
            $table->index('status', 'idx_installments_status');
            $table->foreign('subscription_id', 'fk_installments_subscription')->references('id')->on('subscriptions')->restrictOnDelete();
        });

        DB::statement('ALTER TABLE installments ADD CONSTRAINT chk_installments_number CHECK (installment_number > 0)');
        DB::statement('ALTER TABLE installments ADD CONSTRAINT chk_installments_amount_due CHECK (amount_due >= 0)');
        DB::statement('ALTER TABLE installments ADD CONSTRAINT chk_installments_amount_paid CHECK (amount_paid >= 0 AND amount_paid <= amount_due)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installments');
    }
};

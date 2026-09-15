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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_number', 50)->unique();
            $table->foreignId('customer_id');
            $table->foreignId('plot_id');
            $table->foreignId('payment_plan_id');
            $table->date('subscription_date');
            $table->date('start_date');
            $table->date('expected_end_date')->nullable();
            $table->decimal('contract_total', 12, 2);
            $table->decimal('monthly_amount', 12, 2)->nullable();
            $table->unsignedInteger('duration_months')->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->storedAs('contract_total - amount_paid');
            $table->enum('commercial_status', ['draft', 'pending', 'active', 'suspended', 'cancelled', 'terminated', 'completed'])->default('active');
            $table->enum('financial_status', ['unpaid', 'partially_paid', 'paid', 'overdue'])->default('unpaid');
            $table->enum('administrative_status', ['not_started', 'in_progress', 'validated', 'completed'])->default('not_started');
            $table->unsignedBigInteger('active_plot_guard')->nullable()->storedAs("CASE WHEN commercial_status IN ('pending', 'active', 'suspended') THEN plot_id ELSE NULL END");
            $table->foreignId('created_by')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique('active_plot_guard', 'uq_active_subscription_per_plot');
            $table->index('customer_id', 'idx_subscriptions_customer');
            $table->index('plot_id', 'idx_subscriptions_plot');
            $table->index('payment_plan_id', 'idx_subscriptions_payment_plan');
            $table->index('financial_status', 'idx_subscriptions_financial_status');
            $table->foreign('customer_id', 'fk_subscriptions_customer')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('plot_id', 'fk_subscriptions_plot')->references('id')->on('plots')->restrictOnDelete();
            $table->foreign('payment_plan_id', 'fk_subscriptions_payment_plan')->references('id')->on('payment_plans')->restrictOnDelete();
            $table->foreign('created_by', 'fk_subscriptions_created_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT chk_subscriptions_contract_total CHECK (contract_total >= 0)');
        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT chk_subscriptions_monthly_amount CHECK (monthly_amount IS NULL OR monthly_amount >= 0)');
        DB::statement('ALTER TABLE subscriptions ADD CONSTRAINT chk_subscriptions_amount_paid CHECK (amount_paid >= 0 AND amount_paid <= contract_total)');

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->foreignId('subscription_id')->unique();
            $table->date('signed_at')->nullable();
            $table->text('document_path')->nullable();
            $table->enum('status', ['draft', 'signed', 'cancelled', 'archived'])->default('draft');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->foreign('subscription_id', 'fk_contracts_subscription')->references('id')->on('subscriptions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('subscriptions');
    }
};

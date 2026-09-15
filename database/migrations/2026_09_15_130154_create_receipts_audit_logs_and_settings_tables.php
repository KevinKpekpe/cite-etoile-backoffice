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
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique();
            $table->foreignId('payment_id')->unique();
            $table->foreignId('customer_id');
            $table->foreignId('subscription_id');
            $table->decimal('amount', 12, 2);
            $table->dateTime('issued_at')->useCurrent();
            $table->text('pdf_path')->nullable();
            $table->string('verification_code', 150)->nullable()->unique();
            $table->text('qr_code_value')->nullable();
            $table->foreignId('issued_by')->nullable();
            $table->enum('status', ['valid', 'cancelled'])->default('valid');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index('customer_id', 'idx_receipts_customer');
            $table->index('subscription_id', 'idx_receipts_subscription');
            $table->foreign('payment_id', 'fk_receipts_payment')->references('id')->on('payments')->restrictOnDelete();
            $table->foreign('customer_id', 'fk_receipts_customer')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('subscription_id', 'fk_receipts_subscription')->references('id')->on('subscriptions')->restrictOnDelete();
            $table->foreign('issued_by', 'fk_receipts_issued_by')->references('id')->on('users')->nullOnDelete();
        });

        DB::statement('ALTER TABLE receipts ADD CONSTRAINT chk_receipts_amount CHECK (amount > 0)');

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index('user_id', 'idx_audit_logs_user');
            $table->index(['entity_type', 'entity_id'], 'idx_audit_logs_entity');
            $table->index('created_at', 'idx_audit_logs_created_at');
            $table->foreign('user_id', 'fk_audit_logs_user')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_group', 100);
            $table->string('setting_key', 150);
            $table->longText('value')->nullable();
            $table->enum('value_type', ['string', 'integer', 'decimal', 'boolean', 'json'])->default('string');
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->unique(['setting_group', 'setting_key'], 'uq_settings_group_key');
            $table->foreign('updated_by', 'fk_settings_updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('receipts');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 50)->unique();
            $table->foreignId('user_id')->nullable()->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('phone', 50);
            $table->string('whatsapp', 50)->nullable();
            $table->string('email', 190)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->enum('status', ['prospect', 'active', 'settled', 'suspended', 'archived'])
                ->default('active');
            $table->foreignId('created_by')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index(['last_name', 'first_name'], 'idx_customers_name');
            $table->index('phone', 'idx_customers_phone');
            $table->index('status', 'idx_customers_status');
            $table->foreign('user_id', 'fk_customers_user')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'fk_customers_created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id');
            $table->enum('document_type', [
                'identity', 'photo', 'contract', 'payment_proof', 'subscription_document', 'other',
            ]);
            $table->string('name');
            $table->text('file_path');
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('customer_id', 'idx_customer_documents_customer');
            $table->foreign('customer_id', 'fk_customer_documents_customer')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('uploaded_by', 'fk_customer_documents_uploaded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('customers');
    }
};

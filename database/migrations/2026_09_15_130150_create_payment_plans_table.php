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
        Schema::create('payment_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->decimal('total_price', 12, 2);
            $table->decimal('monthly_amount', 12, 2)->nullable();
            $table->unsignedInteger('duration_months')->default(0);
            $table->enum('frequency', ['once', 'monthly']);
            $table->boolean('active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        DB::statement('ALTER TABLE payment_plans ADD CONSTRAINT chk_payment_plans_total_price CHECK (total_price >= 0)');
        DB::statement('ALTER TABLE payment_plans ADD CONSTRAINT chk_payment_plans_monthly_amount CHECK (monthly_amount IS NULL OR monthly_amount >= 0)');
        DB::statement('ALTER TABLE payment_plans ADD CONSTRAINT chk_payment_plans_dates CHECK (valid_until IS NULL OR valid_from IS NULL OR valid_until >= valid_from)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_plans');
    }
};

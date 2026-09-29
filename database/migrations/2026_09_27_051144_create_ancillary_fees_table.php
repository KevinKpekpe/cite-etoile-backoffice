<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ancillary_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->enum('fee_type', ['survey', 'cadastral_number', 'occupancy_certificate', 'registration_certificate', 'development']);
            $table->unsignedSmallInteger('installment_number')->default(1);
            $table->date('due_date');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->storedAs('amount_due - amount_paid');
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('realized_at')->nullable();
            $table->enum('status', ['upcoming', 'due', 'partially_paid', 'paid', 'overdue'])->default('upcoming');
            $table->timestamps();
            $table->unique(['subscription_id', 'fee_type', 'installment_number'], 'uq_ancillary_fee_schedule');
            $table->index(['due_date', 'status'], 'idx_ancillary_fees_due_status');
        });

        DB::statement('ALTER TABLE ancillary_fees ADD CONSTRAINT chk_ancillary_fees_amounts CHECK (amount_due > 0 AND amount_paid >= 0 AND amount_paid <= amount_due)');
        DB::statement('ALTER TABLE ancillary_fees ADD CONSTRAINT chk_ancillary_fees_installment CHECK (installment_number > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ancillary_fees');
    }
};

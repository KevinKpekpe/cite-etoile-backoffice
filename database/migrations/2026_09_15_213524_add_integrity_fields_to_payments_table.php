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
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique()->after('payment_reference');
            $table->text('proof_path')->nullable()->after('notes');
            $table->text('reversal_reason')->nullable()->after('proof_path');
            $table->foreignId('reversed_by')->nullable()->after('reversal_reason');
            $table->dateTime('reversed_at')->nullable()->after('reversed_by');
            $table->foreign('reversed_by', 'fk_payments_reversed_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign('fk_payments_reversed_by');
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'proof_path', 'reversal_reason', 'reversed_by', 'reversed_at']);
        });
    }
};

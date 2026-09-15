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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('secondary_phone', 50)->nullable()->after('phone');
            $table->string('commune', 100)->nullable()->after('address');
            $table->text('internal_notes')->nullable()->after('nationality');
            $table->foreignId('assigned_to')->nullable()->after('created_by');
            $table->index('assigned_to', 'idx_customers_assigned_to');
            $table->foreign('assigned_to', 'fk_customers_assigned_to')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign('fk_customers_assigned_to');
            $table->dropIndex('idx_customers_assigned_to');
            $table->dropColumn(['secondary_phone', 'commune', 'internal_notes', 'assigned_to']);
        });
    }
};

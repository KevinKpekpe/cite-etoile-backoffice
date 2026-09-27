<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE ancillary_fees MODIFY fee_type VARCHAR(64) NOT NULL');

        Schema::table('ancillary_fees', function (Blueprint $table): void {
            $table->string('fee_label', 150)->nullable()->after('fee_type');
            $table->foreign('fee_type', 'fk_ancillary_fees_type_code')->references('code')->on('ancillary_fee_types')->restrictOnDelete();
        });

        DB::statement("UPDATE ancillary_fees SET fee_label = CASE fee_type WHEN 'survey' THEN 'Bornage' WHEN 'cadastral_number' THEN 'Numéro cadastral' WHEN 'occupancy_certificate' THEN 'Certificat d’occupation' WHEN 'registration_certificate' THEN 'Certificat d’enregistrement' WHEN 'development' THEN 'Aménagement' ELSE fee_type END WHERE fee_label IS NULL");
    }

    public function down(): void
    {
        Schema::table('ancillary_fees', function (Blueprint $table): void {
            $table->dropForeign('fk_ancillary_fees_type_code');
            $table->dropColumn('fee_label');
        });

        DB::statement("ALTER TABLE ancillary_fees MODIFY fee_type ENUM('survey', 'cadastral_number', 'occupancy_certificate', 'registration_certificate', 'development') NOT NULL");
    }
};

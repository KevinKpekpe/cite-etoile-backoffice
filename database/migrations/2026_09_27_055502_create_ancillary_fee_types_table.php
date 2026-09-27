<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ancillary_fee_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name', 150);
            $table->decimal('default_amount', 12, 2)->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        DB::table('ancillary_fee_types')->insert([
            ['code' => 'survey', 'name' => 'Bornage', 'default_amount' => '50.00', 'is_system' => true],
            ['code' => 'cadastral_number', 'name' => 'Numéro cadastral', 'default_amount' => '30.00', 'is_system' => true],
            ['code' => 'occupancy_certificate', 'name' => 'Certificat d’occupation', 'default_amount' => '400.00', 'is_system' => true],
            ['code' => 'registration_certificate', 'name' => 'Certificat d’enregistrement', 'default_amount' => '800.00', 'is_system' => true],
            ['code' => 'development', 'name' => 'Aménagement', 'default_amount' => null, 'is_system' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ancillary_fee_types');
    }
};

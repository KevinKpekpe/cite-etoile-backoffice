<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ancillary_fee_types', function (Blueprint $table): void {
            $table->json('pricing_options')->nullable()->after('default_amount');
        });

        DB::table('ancillary_fee_types')->where('code', 'development')->update([
            'pricing_options' => json_encode([
                'cash' => ['total' => '1800.00', 'monthly' => '60.00'],
                'one_year' => ['total' => '1440.00', 'monthly' => '40.00'],
                'three_years' => ['total' => '1080.00', 'monthly' => '30.00'],
                'five_years' => ['total' => '720.00', 'monthly' => '20.00'],
                'ten_years' => ['total' => '360.00', 'monthly' => '10.00'],
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    public function down(): void
    {
        Schema::table('ancillary_fee_types', function (Blueprint $table): void {
            $table->dropColumn('pricing_options');
        });
    }
};

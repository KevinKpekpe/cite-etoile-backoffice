<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['setting_group' => 'company', 'setting_key' => 'name', 'value' => 'MJIC IMMOBILIER SARL', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'company', 'setting_key' => 'logo', 'value' => '', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'company', 'setting_key' => 'phone', 'value' => '', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'company', 'setting_key' => 'email', 'value' => '', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'company', 'setting_key' => 'address', 'value' => '', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'project', 'setting_key' => 'name', 'value' => 'Cité Étoile du Monde', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'finance', 'setting_key' => 'currency', 'value' => 'USD', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'finance', 'setting_key' => 'payment_methods', 'value' => '["cash","bank_transfer","mobile_money","card","other"]', 'value_type' => 'json', 'is_public' => false],
            ['setting_group' => 'customer', 'setting_key' => 'prefix', 'value' => 'CLI', 'value_type' => 'string', 'is_public' => false],
            ['setting_group' => 'payment', 'setting_key' => 'prefix', 'value' => 'PAY', 'value_type' => 'string', 'is_public' => false],
            ['setting_group' => 'receipt', 'setting_key' => 'prefix', 'value' => 'REC', 'value_type' => 'string', 'is_public' => false],
            ['setting_group' => 'contract', 'setting_key' => 'prefix', 'value' => 'CTR', 'value_type' => 'string', 'is_public' => false],
            ['setting_group' => 'subscription', 'setting_key' => 'allow_partial_payment', 'value' => 'true', 'value_type' => 'boolean', 'is_public' => false],
            ['setting_group' => 'subscription', 'setting_key' => 'allow_advance_payment', 'value' => 'true', 'value_type' => 'boolean', 'is_public' => false],
        ];

        DB::table('settings')->upsert($settings, ['setting_group', 'setting_key'], [
            'value', 'value_type', 'is_public',
        ]);
    }
}

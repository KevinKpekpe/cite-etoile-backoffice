<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            // Platform settings
            ['setting_group' => 'platform', 'setting_key' => 'locale', 'value' => 'fr', 'value_type' => 'string', 'is_public' => false],

            // Client portal settings
            ['setting_group' => 'portal', 'setting_key' => 'site_title', 'value' => 'Espace Client — Cité Étoile du Monde', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'portal', 'setting_key' => 'welcome_message', 'value' => 'Bienvenue sur votre espace client. Retrouvez ici le suivi de vos souscriptions, l\'état de vos paiements et vos documents.', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'portal', 'setting_key' => 'support_email', 'value' => '', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'portal', 'setting_key' => 'support_phone', 'value' => '', 'value_type' => 'string', 'is_public' => true],
            ['setting_group' => 'portal', 'setting_key' => 'allow_profile_edit', 'value' => 'true', 'value_type' => 'boolean', 'is_public' => false],
            ['setting_group' => 'portal', 'setting_key' => 'show_payment_history', 'value' => 'true', 'value_type' => 'boolean', 'is_public' => false],
        ];

        DB::table('settings')->upsert(
            $rows,
            ['setting_group', 'setting_key'],
            ['value', 'value_type', 'is_public']
        );
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('setting_group', 'platform')
            ->orWhere('setting_group', 'portal')
            ->delete();
    }
};

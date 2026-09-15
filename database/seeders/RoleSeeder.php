<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'super_admin', 'description' => 'Accès complet à la plateforme'],
            ['name' => 'admin', 'description' => 'Administration opérationnelle'],
            ['name' => 'commercial', 'description' => 'Gestion commerciale et clients'],
            ['name' => 'cashier', 'description' => 'Gestion des paiements et reçus'],
            ['name' => 'finance_manager', 'description' => 'Supervision financière et rapports'],
            ['name' => 'direction', 'description' => 'Consultation des indicateurs et rapports'],
            ['name' => 'customer', 'description' => 'Accès au portail client'],
        ];

        DB::table('roles')->upsert($roles, ['name'], ['description']);
    }
}

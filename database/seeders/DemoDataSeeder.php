<?php

namespace Database\Seeders;

use App\Models\Avenue;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Neighborhood;
use App\Models\PaymentPlan;
use App\Models\Plot;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Services\InstallmentScheduleService;
use App\Services\PaymentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function __construct(
        private InstallmentScheduleService $scheduleService,
        private PaymentService $paymentService
    ) {}

    public function run(): void
    {
        // 1. Utilisateurs internes et rôles
        $password = Hash::make('password');

        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        $commercialRole = Role::query()->where('name', 'commercial')->firstOrFail();
        $cashierRole = Role::query()->where('name', 'cashier')->firstOrFail();
        $financeRole = Role::query()->where('name', 'finance_manager')->firstOrFail();
        $directionRole = Role::query()->where('name', 'direction')->firstOrFail();
        $customerRole = Role::query()->where('name', 'customer')->firstOrFail();

        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@cite-etoile.cd'],
            ['first_name' => 'Super', 'last_name' => 'Admin', 'phone' => '+243810000001', 'password' => $password, 'status' => 'active']
        );
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@cite-etoile.cd'],
            ['first_name' => 'Alain', 'last_name' => 'Mukendi', 'phone' => '+243810000002', 'password' => $password, 'status' => 'active']
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        $commercial1 = User::query()->firstOrCreate(
            ['email' => 'commercial@cite-etoile.cd'],
            ['first_name' => 'Joseph', 'last_name' => 'Bakanza', 'phone' => '+243820000003', 'password' => $password, 'status' => 'active']
        );
        $commercial1->roles()->syncWithoutDetaching([$commercialRole->id]);

        $commercial2 = User::query()->firstOrCreate(
            ['email' => 'n.mwamba@cite-etoile.cd'],
            ['first_name' => 'Nadine', 'last_name' => 'Mwamba', 'phone' => '+243840000004', 'password' => $password, 'status' => 'active']
        );
        $commercial2->roles()->syncWithoutDetaching([$commercialRole->id]);

        $cashier = User::query()->firstOrCreate(
            ['email' => 'caissier@cite-etoile.cd'],
            ['first_name' => 'Ruth', 'last_name' => 'Bolamba', 'phone' => '+243850000005', 'password' => $password, 'status' => 'active']
        );
        $cashier->roles()->syncWithoutDetaching([$cashierRole->id]);

        $finance = User::query()->firstOrCreate(
            ['email' => 'finance@cite-etoile.cd'],
            ['first_name' => 'Jean-Marc', 'last_name' => 'Mbayo', 'phone' => '+243890000006', 'password' => $password, 'status' => 'active']
        );
        $finance->roles()->syncWithoutDetaching([$financeRole->id]);

        $direction = User::query()->firstOrCreate(
            ['email' => 'direction@cite-etoile.cd'],
            ['first_name' => 'Roger', 'last_name' => 'Kiala', 'phone' => '+243990000007', 'password' => $password, 'status' => 'active']
        );
        $direction->roles()->syncWithoutDetaching([$directionRole->id]);

        // 2. Quartiers et Avenues
        $demoNeighborhood = Neighborhood::query()->updateOrCreate(
            ['code' => 'DEMO-Q1'],
            ['name' => 'Quartier Démonstration', 'description' => 'Données fictives locales', 'status' => 'active']
        );

        $demoAvenue = Avenue::query()->updateOrCreate(
            ['neighborhood_id' => $demoNeighborhood->id, 'code' => 'DEMO-AV1'],
            ['name' => 'Avenue Démonstration', 'status' => 'active']
        );

        $demoPlot = Plot::query()->updateOrCreate(
            ['reference' => 'DEMO-LOT-0001'],
            [
                'plot_number' => 'DEMO-P001',
                'avenue_id' => $demoAvenue->id,
                'surface_area' => '300.00',
                'width' => '15.00',
                'length' => '20.00',
                'base_price' => '2500.00',
                'commercial_status' => 'subscribed',
            ]
        );

        $demoCustomer = Customer::query()->updateOrCreate(
            ['customer_number' => 'DEMO-CLI-0001'],
            [
                'first_name' => 'Client',
                'last_name' => 'Démonstration',
                'phone' => '+243000000000',
                'email' => 'demo@example.test',
                'status' => 'active',
            ]
        );

        $planCash = PaymentPlan::query()->where('code', 'CASH')->firstOrFail();

        Subscription::query()->updateOrCreate(
            ['subscription_number' => 'DEMO-SUB-0001'],
            [
                'customer_id' => $demoCustomer->id,
                'plot_id' => $demoPlot->id,
                'payment_plan_id' => $planCash->id,
                'subscription_date' => '2026-01-01',
                'start_date' => '2026-01-01',
                'expected_end_date' => '2026-01-01',
                'contract_total' => '2500.00',
                'monthly_amount' => null,
                'duration_months' => 0,
                'commercial_status' => 'active',
            ]
        );

        $qAstres = Neighborhood::query()->updateOrCreate(
            ['code' => 'Q-ASTRES'],
            ['name' => 'Quartier Les Astres', 'description' => 'Quartier résidentiel principal de la Cité Étoile du Monde', 'status' => 'active']
        );

        $qGalaxies = Neighborhood::query()->updateOrCreate(
            ['code' => 'Q-GALAXIES'],
            ['name' => 'Quartier Les Galaxies', 'description' => 'Zone résidentielle haute standing', 'status' => 'commercializable']
        );

        $qOrion = Neighborhood::query()->updateOrCreate(
            ['code' => 'Q-ORION'],
            ['name' => 'Quartier Orion', 'description' => 'Extension Est du projet immobilier', 'status' => 'active']
        );

        $avSoleil = Avenue::query()->updateOrCreate(
            ['neighborhood_id' => $qAstres->id, 'code' => 'AV-SOL'],
            ['name' => 'Avenue Soleil', 'description' => 'Avenue principale bordée d\'arbres', 'status' => 'active']
        );

        $avLune = Avenue::query()->updateOrCreate(
            ['neighborhood_id' => $qAstres->id, 'code' => 'AV-LUN'],
            ['name' => 'Avenue Lune', 'description' => 'Avenue parallèle de desserte', 'status' => 'active']
        );

        $avEtoile = Avenue::query()->updateOrCreate(
            ['neighborhood_id' => $qGalaxies->id, 'code' => 'AV-ETO'],
            ['name' => 'Avenue Étoile', 'description' => 'Avenue centrale du quartier Les Galaxies', 'status' => 'active']
        );

        $avTerre = Avenue::query()->updateOrCreate(
            ['neighborhood_id' => $qGalaxies->id, 'code' => 'AV-TER'],
            ['name' => 'Avenue Terre', 'description' => 'Avenue calme à usage résidentiel exclusif', 'status' => 'active']
        );

        $avJupiter = Avenue::query()->updateOrCreate(
            ['neighborhood_id' => $qOrion->id, 'code' => 'AV-JUP'],
            ['name' => 'Avenue Jupiter', 'description' => 'Avenue pavée du quartier Orion', 'status' => 'active']
        );

        // 3. Parcelles (Plots)
        $plotData = [
            // Avenue Soleil
            ['ref' => 'PLOT-SOL-001', 'num' => 'P-001', 'av' => $avSoleil, 'surf' => 300, 'w' => 15, 'l' => 20, 'price' => 2500, 'status' => 'subscribed'],
            ['ref' => 'PLOT-SOL-002', 'num' => 'P-002', 'av' => $avSoleil, 'surf' => 300, 'w' => 15, 'l' => 20, 'price' => 2500, 'status' => 'subscribed'],
            ['ref' => 'PLOT-SOL-003', 'num' => 'P-003', 'av' => $avSoleil, 'surf' => 300, 'w' => 15, 'l' => 20, 'price' => 2500, 'status' => 'subscribed'],
            ['ref' => 'PLOT-SOL-004', 'num' => 'P-004', 'av' => $avSoleil, 'surf' => 400, 'w' => 20, 'l' => 20, 'price' => 3500, 'status' => 'reserved'],
            ['ref' => 'PLOT-SOL-005', 'num' => 'P-005', 'av' => $avSoleil, 'surf' => 400, 'w' => 20, 'l' => 20, 'price' => 3500, 'status' => 'available'],
            ['ref' => 'PLOT-SOL-006', 'num' => 'P-006', 'av' => $avSoleil, 'surf' => 500, 'w' => 20, 'l' => 25, 'price' => 4500, 'status' => 'available'],

            // Avenue Lune
            ['ref' => 'PLOT-LUN-001', 'num' => 'P-101', 'av' => $avLune, 'surf' => 300, 'w' => 15, 'l' => 20, 'price' => 2500, 'status' => 'subscribed'],
            ['ref' => 'PLOT-LUN-002', 'num' => 'P-102', 'av' => $avLune, 'surf' => 300, 'w' => 15, 'l' => 20, 'price' => 2500, 'status' => 'subscribed'],
            ['ref' => 'PLOT-LUN-003', 'num' => 'P-103', 'av' => $avLune, 'surf' => 400, 'w' => 20, 'l' => 20, 'price' => 3500, 'status' => 'available'],
            ['ref' => 'PLOT-LUN-004', 'num' => 'P-104', 'av' => $avLune, 'surf' => 400, 'w' => 20, 'l' => 20, 'price' => 3500, 'status' => 'blocked'],

            // Avenue Étoile
            ['ref' => 'PLOT-ETO-001', 'num' => 'P-201', 'av' => $avEtoile, 'surf' => 500, 'w' => 20, 'l' => 25, 'price' => 5000, 'status' => 'subscribed'],
            ['ref' => 'PLOT-ETO-002', 'num' => 'P-202', 'av' => $avEtoile, 'surf' => 500, 'w' => 20, 'l' => 25, 'price' => 5000, 'status' => 'available'],
            ['ref' => 'PLOT-ETO-003', 'num' => 'P-203', 'av' => $avEtoile, 'surf' => 500, 'w' => 20, 'l' => 25, 'price' => 5000, 'status' => 'available'],

            // Avenue Terre
            ['ref' => 'PLOT-TER-001', 'num' => 'P-301', 'av' => $avTerre, 'surf' => 300, 'w' => 15, 'l' => 20, 'price' => 2500, 'status' => 'available'],
            ['ref' => 'PLOT-TER-002', 'num' => 'P-302', 'av' => $avTerre, 'surf' => 300, 'w' => 15, 'l' => 20, 'price' => 2500, 'status' => 'available'],

            // Avenue Jupiter
            ['ref' => 'PLOT-JUP-001', 'num' => 'P-401', 'av' => $avJupiter, 'surf' => 400, 'w' => 20, 'l' => 20, 'price' => 3600, 'status' => 'subscribed'],
            ['ref' => 'PLOT-JUP-002', 'num' => 'P-402', 'av' => $avJupiter, 'surf' => 400, 'w' => 20, 'l' => 20, 'price' => 3600, 'status' => 'available'],
        ];

        $plots = [];
        foreach ($plotData as $p) {
            $plots[$p['ref']] = Plot::query()->updateOrCreate(
                ['reference' => $p['ref']],
                [
                    'plot_number' => $p['num'],
                    'avenue_id' => $p['av']->id,
                    'surface_area' => $p['surf'],
                    'width' => $p['w'],
                    'length' => $p['l'],
                    'base_price' => $p['price'],
                    'commercial_status' => $p['status'],
                    'financial_status' => 'unpaid',
                    'administrative_status' => 'not_started',
                ]
            );
        }

        // 4. Formules d'acquisition
        $planCash = PaymentPlan::query()->where('code', 'CASH')->firstOrFail();
        $planCredit1Y = PaymentPlan::query()->where('code', 'CREDIT_1Y')->firstOrFail();
        $planCredit3Y = PaymentPlan::query()->where('code', 'CREDIT_3Y')->firstOrFail();
        $planCredit5Y = PaymentPlan::query()->where('code', 'CREDIT_5Y')->firstOrFail();

        // 5. Clients Réels (Kinshasa, RDC)
        $customersData = [
            [
                'num' => 'CLI-2026-0001', 'first' => 'Jean-Baptiste', 'last' => 'Mukendi',
                'phone' => '+243818901234', 'whatsapp' => '+243818901234', 'email' => 'jb.mukendi@gmail.com',
                'address' => '12, Avenue de la Justice, Commune de la Gombe', 'city' => 'Kinshasa', 'country' => 'RDC',
                'status' => 'active', 'user_email' => 'jb.mukendi@gmail.com',
            ],
            [
                'num' => 'CLI-2026-0002', 'first' => 'Grace', 'last' => 'Kapinga',
                'phone' => '+243825509876', 'whatsapp' => '+243825509876', 'email' => 'grace.kapinga@outlook.com',
                'address' => '45, Avenue Colonel Mondjiba, Ngaliema', 'city' => 'Kinshasa', 'country' => 'RDC',
                'status' => 'active', 'user_email' => 'grace.kapinga@outlook.com',
            ],
            [
                'num' => 'CLI-2026-0003', 'first' => 'Emmanuel', 'last' => 'Lukusa',
                'phone' => '+243841122334', 'whatsapp' => '+243841122334', 'email' => 'emmanuel.lukusa@yahoo.fr',
                'address' => '78, Chaussée de Mbenkana, Mont-Ngafula', 'city' => 'Kinshasa', 'country' => 'RDC',
                'status' => 'active', 'user_email' => 'emmanuel.lukusa@yahoo.fr',
            ],
            [
                'num' => 'CLI-2026-0004', 'first' => 'Patrick', 'last' => 'Mavungu',
                'phone' => '+243859988776', 'whatsapp' => '+243859988776', 'email' => 'patrick.mavungu@gmail.com',
                'address' => '102, Avenue 14ème Rue, Limete Résidentiel', 'city' => 'Kinshasa', 'country' => 'RDC',
                'status' => 'active', 'user_email' => 'patrick.mavungu@gmail.com',
            ],
            [
                'num' => 'CLI-2026-0005', 'first' => 'Marie-Claire', 'last' => 'Kabedi',
                'phone' => '+243897766554', 'whatsapp' => '+243897766554', 'email' => 'mc.kabedi@gmail.com',
                'address' => '23, Avenue Bangala, Kintambo', 'city' => 'Kinshasa', 'country' => 'RDC',
                'status' => 'active', 'user_email' => 'mc.kabedi@gmail.com',
            ],
            [
                'num' => 'CLI-2026-0006', 'first' => 'Chantal', 'last' => 'Ngalula',
                'phone' => '+243991234567', 'whatsapp' => '+243991234567', 'email' => 'chantal.ngalula@hotmail.com',
                'address' => '88, Boulevard du 30 Juin, Gombe', 'city' => 'Kinshasa', 'country' => 'RDC',
                'status' => 'active', 'user_email' => 'chantal.ngalula@hotmail.com',
            ],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $clientUser = User::query()->firstOrCreate(
                ['email' => $c['user_email']],
                [
                    'first_name' => $c['first'],
                    'last_name' => $c['last'],
                    'phone' => $c['phone'],
                    'password' => $password,
                    'status' => 'active',
                ]
            );
            $clientUser->roles()->syncWithoutDetaching([$customerRole->id]);

            $customers[$c['num']] = Customer::query()->updateOrCreate(
                ['customer_number' => $c['num']],
                [
                    'user_id' => $clientUser->id,
                    'first_name' => $c['first'],
                    'last_name' => $c['last'],
                    'phone' => $c['phone'],
                    'whatsapp' => $c['whatsapp'],
                    'email' => $c['email'],
                    'address' => $c['address'],
                    'city' => $c['city'],
                    'country' => $c['country'],
                    'status' => $c['status'],
                    'created_by' => $commercial1->id,
                ]
            );
        }

        // 6. Souscriptions & Scénarios financiers réalistes

        // SCÉNARIO 1 : Client Mukendi Jean-Baptiste (Paiement Cash totalisé - Contrat soldé)
        $sub1Plot = $plots['PLOT-SOL-001'];
        $sub1Customer = $customers['CLI-2026-0001'];
        $sub1 = Subscription::query()->updateOrCreate(
            ['subscription_number' => 'SUB-2026-0001'],
            [
                'customer_id' => $sub1Customer->id,
                'plot_id' => $sub1Plot->id,
                'payment_plan_id' => $planCash->id,
                'subscription_date' => '2026-01-15',
                'start_date' => '2026-01-15',
                'expected_end_date' => '2026-01-15',
                'contract_total' => '2500.00',
                'monthly_amount' => null,
                'duration_months' => 0,
                'commercial_status' => 'completed',
                'financial_status' => 'paid',
                'administrative_status' => 'validated',
                'created_by' => $commercial1->id,
            ]
        );

        Contract::query()->updateOrCreate(
            ['subscription_id' => $sub1->id],
            [
                'contract_number' => 'CTR-2026-0001',
                'signed_at' => '2026-01-15',
                'status' => 'signed',
            ]
        );

        $this->paymentService->record($sub1, $cashier, [
            'amount' => '2500.00',
            'currency' => 'USD',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'RAW-TX-998822',
            'payment_date' => '2026-01-15 10:30:00',
            'idempotency_key' => 'SEED-PAY-0001',
            'notes' => 'Paiement comptant par virement Rawbank',
        ]);

        // SCÉNARIO 2 : Client Grace Kapinga (Crédit 1 an - 4 mensualités payées régulièrement)
        $sub2Plot = $plots['PLOT-SOL-002'];
        $sub2Customer = $customers['CLI-2026-0002'];
        $sub2 = Subscription::query()->updateOrCreate(
            ['subscription_number' => 'SUB-2026-0002'],
            [
                'customer_id' => $sub2Customer->id,
                'plot_id' => $sub2Plot->id,
                'payment_plan_id' => $planCredit1Y->id,
                'subscription_date' => '2026-02-01',
                'start_date' => '2026-02-01',
                'expected_end_date' => '2027-02-01',
                'contract_total' => '3600.00',
                'monthly_amount' => '300.00',
                'duration_months' => 12,
                'commercial_status' => 'active',
                'financial_status' => 'partially_paid',
                'administrative_status' => 'in_progress',
                'created_by' => $commercial1->id,
            ]
        );

        Contract::query()->updateOrCreate(
            ['subscription_id' => $sub2->id],
            [
                'contract_number' => 'CTR-2026-0002',
                'signed_at' => '2026-02-01',
                'status' => 'signed',
            ]
        );

        $this->scheduleService->generate($sub2);

        // 4 paiements mensuels
        $this->paymentService->record($sub2, $cashier, [
            'amount' => '300.00', 'currency' => 'USD', 'payment_method' => 'mobile_money',
            'transaction_reference' => 'MPESA-887123', 'payment_date' => '2026-03-01 14:00:00',
            'idempotency_key' => 'SEED-PAY-0002-1', 'notes' => 'Mensualité Mars 2026 M-Pesa',
        ]);
        $this->paymentService->record($sub2, $cashier, [
            'amount' => '300.00', 'currency' => 'USD', 'payment_method' => 'mobile_money',
            'transaction_reference' => 'MPESA-891002', 'payment_date' => '2026-04-01 11:15:00',
            'idempotency_key' => 'SEED-PAY-0002-2', 'notes' => 'Mensualité Avril 2026 M-Pesa',
        ]);
        $this->paymentService->record($sub2, $cashier, [
            'amount' => '300.00', 'currency' => 'USD', 'payment_method' => 'cash',
            'transaction_reference' => 'CASH-REC-003', 'payment_date' => '2026-05-02 09:45:00',
            'idempotency_key' => 'SEED-PAY-0002-3', 'notes' => 'Mensualité Mai 2026 en espèces au guichet',
        ]);
        $this->paymentService->record($sub2, $cashier, [
            'amount' => '300.00', 'currency' => 'USD', 'payment_method' => 'mobile_money',
            'transaction_reference' => 'ORANGE-441290', 'payment_date' => '2026-06-01 16:20:00',
            'idempotency_key' => 'SEED-PAY-0002-4', 'notes' => 'Mensualité Juin 2026 Orange Money',
        ]);

        // SCÉNARIO 3 : Client Emmanuel Lukusa (Crédit 3 ans - 2 mensualités payées, retard)
        $sub3Plot = $plots['PLOT-SOL-003'];
        $sub3Customer = $customers['CLI-2026-0003'];
        $sub3 = Subscription::query()->updateOrCreate(
            ['subscription_number' => 'SUB-2026-0003'],
            [
                'customer_id' => $sub3Customer->id,
                'plot_id' => $sub3Plot->id,
                'payment_plan_id' => $planCredit3Y->id,
                'subscription_date' => '2026-01-10',
                'start_date' => '2026-01-10',
                'expected_end_date' => '2029-01-10',
                'contract_total' => '6200.00',
                'monthly_amount' => '175.00',
                'duration_months' => 36,
                'commercial_status' => 'active',
                'financial_status' => 'partially_paid',
                'administrative_status' => 'in_progress',
                'created_by' => $commercial2->id,
            ]
        );

        Contract::query()->updateOrCreate(
            ['subscription_id' => $sub3->id],
            [
                'contract_number' => 'CTR-2026-0003',
                'signed_at' => '2026-01-10',
                'status' => 'signed',
            ]
        );

        $this->scheduleService->generate($sub3);

        $this->paymentService->record($sub3, $cashier, [
            'amount' => '175.00', 'currency' => 'USD', 'payment_method' => 'cash',
            'transaction_reference' => 'CASH-REC-010', 'payment_date' => '2026-02-10 10:00:00',
            'idempotency_key' => 'SEED-PAY-0003-1', 'notes' => '1ère mensualité espèces',
        ]);
        $this->paymentService->record($sub3, $cashier, [
            'amount' => '175.00', 'currency' => 'USD', 'payment_method' => 'cash',
            'transaction_reference' => 'CASH-REC-022', 'payment_date' => '2026-03-10 11:30:00',
            'idempotency_key' => 'SEED-PAY-0003-2', 'notes' => '2ème mensualité espèces',
        ]);

        // SCÉNARIO 4 : Client Patrick Mavungu (Paiement Cash sur parcelle Lune)
        $sub4Plot = $plots['PLOT-LUN-001'];
        $sub4Customer = $customers['CLI-2026-0004'];
        $sub4 = Subscription::query()->updateOrCreate(
            ['subscription_number' => 'SUB-2026-0004'],
            [
                'customer_id' => $sub4Customer->id,
                'plot_id' => $sub4Plot->id,
                'payment_plan_id' => $planCash->id,
                'subscription_date' => '2026-03-01',
                'start_date' => '2026-03-01',
                'expected_end_date' => '2026-03-01',
                'contract_total' => '2500.00',
                'monthly_amount' => null,
                'duration_months' => 0,
                'commercial_status' => 'completed',
                'financial_status' => 'paid',
                'administrative_status' => 'validated',
                'created_by' => $commercial1->id,
            ]
        );

        Contract::query()->updateOrCreate(
            ['subscription_id' => $sub4->id],
            [
                'contract_number' => 'CTR-2026-0004',
                'signed_at' => '2026-03-01',
                'status' => 'signed',
            ]
        );

        $this->paymentService->record($sub4, $cashier, [
            'amount' => '2500.00', 'currency' => 'USD', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'EQB-TX-554411', 'payment_date' => '2026-03-01 15:00:00',
            'idempotency_key' => 'SEED-PAY-0004', 'notes' => 'Virement bancaire Equity BCDC',
        ]);

        // SCÉNARIO 5 : Marie-Claire Kabedi (Crédit 5 ans - Quartier Galaxies Avenue Étoile)
        $sub5Plot = $plots['PLOT-ETO-001'];
        $sub5Customer = $customers['CLI-2026-0005'];
        $sub5 = Subscription::query()->updateOrCreate(
            ['subscription_number' => 'SUB-2026-0005'],
            [
                'customer_id' => $sub5Customer->id,
                'plot_id' => $sub5Plot->id,
                'payment_plan_id' => $planCredit5Y->id,
                'subscription_date' => '2026-04-15',
                'start_date' => '2026-04-15',
                'expected_end_date' => '2031-04-15',
                'contract_total' => '7500.00',
                'monthly_amount' => '125.00',
                'duration_months' => 60,
                'commercial_status' => 'active',
                'financial_status' => 'partially_paid',
                'administrative_status' => 'in_progress',
                'created_by' => $commercial2->id,
            ]
        );

        Contract::query()->updateOrCreate(
            ['subscription_id' => $sub5->id],
            [
                'contract_number' => 'CTR-2026-0005',
                'signed_at' => '2026-04-15',
                'status' => 'signed',
            ]
        );

        $this->scheduleService->generate($sub5);

        $this->paymentService->record($sub5, $cashier, [
            'amount' => '500.00', 'currency' => 'USD', 'payment_method' => 'bank_transfer',
            'transaction_reference' => 'RAW-TX-100293', 'payment_date' => '2026-04-15 14:30:00',
            'idempotency_key' => 'SEED-PAY-0005-1', 'notes' => 'Acompte initial 4 mensualités (500 USD)',
        ]);

        // SCÉNARIO 6 : Chantal Ngalula (Souscription Crédit 1 an - Parcelle Jupiter)
        $sub6Plot = $plots['PLOT-JUP-001'];
        $sub6Customer = $customers['CLI-2026-0006'];
        $sub6 = Subscription::query()->updateOrCreate(
            ['subscription_number' => 'SUB-2026-0006'],
            [
                'customer_id' => $sub6Customer->id,
                'plot_id' => $sub6Plot->id,
                'payment_plan_id' => $planCredit1Y->id,
                'subscription_date' => '2026-05-01',
                'start_date' => '2026-05-01',
                'expected_end_date' => '2027-05-01',
                'contract_total' => '3600.00',
                'monthly_amount' => '300.00',
                'duration_months' => 12,
                'commercial_status' => 'active',
                'financial_status' => 'partially_paid',
                'administrative_status' => 'in_progress',
                'created_by' => $commercial1->id,
            ]
        );

        Contract::query()->updateOrCreate(
            ['subscription_id' => $sub6->id],
            [
                'contract_number' => 'CTR-2026-0006',
                'signed_at' => '2026-05-01',
                'status' => 'signed',
            ]
        );

        $this->scheduleService->generate($sub6);

        $this->paymentService->record($sub6, $cashier, [
            'amount' => '600.00', 'currency' => 'USD', 'payment_method' => 'mobile_money',
            'transaction_reference' => 'MPESA-990011', 'payment_date' => '2026-05-01 16:00:00',
            'idempotency_key' => 'SEED-PAY-0006-1', 'notes' => 'Acompte initial de 2 mois (600 USD)',
        ]);
    }
}

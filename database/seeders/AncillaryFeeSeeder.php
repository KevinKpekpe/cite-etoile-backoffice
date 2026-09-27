<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Services\AncillaryFeeService;
use Illuminate\Database\Seeder;

class AncillaryFeeSeeder extends Seeder
{
    public function run(AncillaryFeeService $fees): void
    {
        Contract::query()
            ->with('subscription')
            ->where('status', 'signed')
            ->whereNotNull('signed_at')
            ->orderBy('id')
            ->chunkById(100, function ($contracts) use ($fees): void {
                foreach ($contracts as $contract) {
                    $fees->createForSignedContract($contract);
                }
            });
    }
}

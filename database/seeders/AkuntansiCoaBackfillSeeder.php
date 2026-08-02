<?php

namespace Database\Seeders;

use App\Models\Mitra;
use App\Services\MitraPos\AkuntansiCoaService;
use Illuminate\Database\Seeder;

/**
 * One-time-per-mitra backfill: mitras enrolled in POS BEFORE the Akuntansi
 * module shipped never went through MitraPosManageController::store()'s
 * seedForMitra() call, so they'd have zero akuntansi_accounts rows — the
 * very next sale would then fail checkout() entirely, since
 * AkuntansiJournalService::postForSale() throws when a required system_role
 * account can't be found (by design — the sale must not silently skip the
 * books). Idempotent (seedForMitra() no-ops if already seeded), safe to run
 * on every deploy alongside MitraPosMenuSeeder.
 */
class AkuntansiCoaBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(AkuntansiCoaService::class);
        $count = 0;

        Mitra::whereHas('posSetting')->get()->each(function (Mitra $mitra) use ($service, &$count) {
            $service->seedForMitra($mitra->id);
            $count++;
        });

        $this->command->info("✅ Akuntansi COA backfill checked for {$count} enrolled mitra(s).");
    }
}

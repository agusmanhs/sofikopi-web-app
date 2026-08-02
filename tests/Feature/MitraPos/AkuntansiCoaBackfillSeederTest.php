<?php

namespace Tests\Feature\MitraPos;

use App\Models\AkuntansiAccount;
use App\Models\Mitra;
use App\Models\MitraCategory;
use App\Models\MitraPosSetting;
use Database\Seeders\AkuntansiCoaBackfillSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the deploy-time backfill for mitras enrolled BEFORE the Akuntansi
 * module existed — see AkuntansiCoaBackfillSeeder's docblock.
 */
class AkuntansiCoaBackfillSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfills_coa_for_a_pre_existing_enrolled_mitra_with_no_accounts_yet(): void
    {
        $this->seed(MitraPosMenuSeeder::class);

        $category = MitraCategory::firstOrCreate(['name' => 'Test Category'], ['is_active' => true]);
        $mitra = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'OLD-MITRA',
            'name' => 'Mitra Lama',
            'is_active' => true,
        ]);
        // Enrolled the "old way" — bypassing seedForMitra(), simulating a
        // mitra enrolled before this module shipped.
        MitraPosSetting::create(['mitra_id' => $mitra->id]);

        $this->assertSame(0, AkuntansiAccount::forMitra($mitra->id)->count());

        $this->seed(AkuntansiCoaBackfillSeeder::class);

        $this->assertSame(190, AkuntansiAccount::forMitra($mitra->id)->count());
    }

    public function test_is_idempotent_and_skips_mitras_not_enrolled_in_pos(): void
    {
        $category = MitraCategory::firstOrCreate(['name' => 'Test Category'], ['is_active' => true]);
        $unenrolled = Mitra::create([
            'mitra_category_id' => $category->id,
            'code' => 'NOT-ENROLLED',
            'name' => 'Belum Ikut POS',
            'is_active' => true,
        ]);

        $this->seed(AkuntansiCoaBackfillSeeder::class);
        $this->seed(AkuntansiCoaBackfillSeeder::class);

        $this->assertSame(0, AkuntansiAccount::forMitra($unenrolled->id)->count());
    }
}

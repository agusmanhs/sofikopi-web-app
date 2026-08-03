<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Models\PosTransaction;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Receipt logo upload (owner-facing, Pengaturan page) and its render on the
 * printed struk. Covers: upload stores the file + path, replacing an
 * existing logo cleans up the old file, remove_logo clears it, and the
 * receipt view only prints the <img> when a logo is set.
 */
class MitraSettingLogoTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
    }

    private function baseSettingPayload(): array
    {
        return [
            'service_charge_percent' => 0,
            'tax_percent' => 0,
            'qris_fee_percent' => 0,
            'transfer_fee_percent' => 0,
            'edc_fee_percent' => 0,
        ];
    }

    public function test_owner_can_upload_a_receipt_logo(): void
    {
        $file = UploadedFile::fake()->image('logo.jpg', 400, 400);

        $response = $this->actingAs($this->owner)->put(route('mitra-setting.update'), array_merge(
            $this->baseSettingPayload(),
            ['receipt_logo' => $file]
        ));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('mitra-setting.index'));

        $setting = MitraPosSetting::forMitra($this->mitra->id)->firstOrFail();
        $this->assertNotNull($setting->receipt_logo_path);
        Storage::disk('public')->assertExists($setting->receipt_logo_path);
    }

    public function test_replacing_logo_deletes_the_old_file(): void
    {
        $this->actingAs($this->owner)->put(route('mitra-setting.update'), array_merge(
            $this->baseSettingPayload(),
            ['receipt_logo' => UploadedFile::fake()->image('first.jpg')]
        ));

        $oldPath = MitraPosSetting::forMitra($this->mitra->id)->firstOrFail()->receipt_logo_path;

        $this->actingAs($this->owner)->put(route('mitra-setting.update'), array_merge(
            $this->baseSettingPayload(),
            ['receipt_logo' => UploadedFile::fake()->image('second.jpg')]
        ));

        $newPath = MitraPosSetting::forMitra($this->mitra->id)->firstOrFail()->receipt_logo_path;

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_remove_logo_clears_the_path_and_deletes_file(): void
    {
        $this->actingAs($this->owner)->put(route('mitra-setting.update'), array_merge(
            $this->baseSettingPayload(),
            ['receipt_logo' => UploadedFile::fake()->image('logo.jpg')]
        ));

        $path = MitraPosSetting::forMitra($this->mitra->id)->firstOrFail()->receipt_logo_path;

        $this->actingAs($this->owner)->put(route('mitra-setting.update'), array_merge(
            $this->baseSettingPayload(),
            ['remove_logo' => '1']
        ));

        $this->assertNull(MitraPosSetting::forMitra($this->mitra->id)->firstOrFail()->receipt_logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_receipt_prints_logo_when_set(): void
    {
        $this->actingAs($this->owner)->put(route('mitra-setting.update'), array_merge(
            $this->baseSettingPayload(),
            ['receipt_logo' => UploadedFile::fake()->image('logo.jpg')]
        ));

        $transaction = PosTransaction::create([
            'mitra_id' => $this->mitra->id,
            'transaction_no' => 'POS/LALLO/20260803/0001',
            'sales_mode' => 'dine_in',
            'payment_method' => 'cash',
            'subtotal' => 10000,
            'discount' => 0,
            'service_charge' => 0,
            'tax' => 0,
            'grand_total' => 10000,
            'total_hpp' => 0,
            'total_cogs' => 0,
            'admin_fee' => 0,
            'status' => 'completed',
            'user_id' => $this->owner->id,
            'transacted_at' => now(),
        ]);

        $response = $this->actingAs($this->owner)->get(route('pos-transaction.receipt', $transaction->transaction_no));

        $response->assertOk();
        $response->assertSee('<img', false);
    }
}

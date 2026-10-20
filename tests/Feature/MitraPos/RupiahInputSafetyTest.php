<?php

namespace Tests\Feature\MitraPos;

use App\Models\Mitra;
use App\Models\MitraPosSetting;
use App\Models\User;
use Database\Seeders\CafeLalloPosSeeder;
use Database\Seeders\MitraPosMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nilai uang desimal dari database ("15000000.00") tidak boleh berubah 100x
 * karena titiknya dianggap pemisah ribuan.
 */
class RupiahInputSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Mitra $mitra;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(MitraPosMenuSeeder::class);
        $this->seed(CafeLalloPosSeeder::class);

        $this->mitra = Mitra::where('code', 'CAFE-LALLO-KDI')->firstOrFail();
        $this->owner = User::where('email', 'owner@cafelallo.test')->firstOrFail();
    }

    private function payload(string $target): array
    {
        return [
            'monthly_revenue_target' => $target,
            'service_charge_percent' => 0,
            'tax_percent' => 0,
            'qris_fee_percent' => 0,
            'transfer_fee_percent' => 0,
            'edc_fee_percent' => 0,
        ];
    }

    private function storedTarget(): float
    {
        return (float) MitraPosSetting::forMitra($this->mitra->id)->firstOrFail()->monthly_revenue_target;
    }

    public function test_settings_page_prefills_target_in_indonesian_format_not_raw_decimal(): void
    {
        MitraPosSetting::forMitra($this->mitra->id)->update(['monthly_revenue_target' => 15000000]);

        $response = $this->actingAs($this->owner)->get(route('mitra-setting.index'));

        $response->assertOk();
        $response->assertSee('value="15.000.000"', false);
        $this->assertDoesNotMatchRegularExpression(
            '/name="monthly_revenue_target"[^>]*value="15000000/',
            $response->getContent()
        );
    }

    public function test_masked_value_is_stored_as_typed(): void
    {
        $this->actingAs($this->owner)->put(route('mitra-setting.update'), $this->payload('15.000.000'))
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(15000000.0, $this->storedTarget(), 0.001);
    }

    public function test_raw_database_decimal_is_not_multiplied_by_one_hundred(): void
    {
        $this->actingAs($this->owner)->put(route('mitra-setting.update'), $this->payload('15000000.00'))
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(15000000.0, $this->storedTarget(), 0.001);
    }

    public function test_saving_the_page_twice_does_not_change_the_target(): void
    {
        MitraPosSetting::forMitra($this->mitra->id)->update(['monthly_revenue_target' => 10000]);

        $html = $this->actingAs($this->owner)->get(route('mitra-setting.index'))->getContent();
        preg_match('/name="monthly_revenue_target"[^>]*value="([^"]*)"/', $html, $match);

        $this->actingAs($this->owner)->put(route('mitra-setting.update'), $this->payload($match[1]))
            ->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(10000.0, $this->storedTarget(), 0.001);
    }
}

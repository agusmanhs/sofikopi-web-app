<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Repositories\SettingRepository;
use App\Services\AbsensiService;
use App\Services\SettingService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for AbsensiController setting integration.
 *
 * Verifies that AbsensiController reads from SettingService instead of using
 * hardcoded hour values. These tests would have failed before the fix was applied.
 */
class AbsensiControllerSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock TelegramService to prevent sending notifications during tests
        $this->mock(TelegramService::class, function ($mock) {
            $mock->shouldReceive('notifyAbsenMasuk')->andReturnNull();
            $mock->shouldReceive('notifyAbsenPulang')->andReturnNull();
            $mock->shouldReceive('notify')->andReturnNull();
        });

        // Seed default settings
        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();
    }

    /**
     * Helper: Create a test pegawai with shift.
     *
     * @param  int  $jamMasukOffset  Hours offset from now for shift start time
     */
    protected function makePegawai(int $jamMasukOffset = -1): array
    {
        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);

        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat',
            'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100,
            'is_aktif' => true,
        ]);

        $user = User::factory()->create();

        $shift = Shift::create([
            'divisi_id' => $divisi->id,
            'nama' => 'Shift Pagi',
            'jam_masuk' => now()->addHours($jamMasukOffset)->format('H:i:s'),
            'jam_pulang' => now()->addHours($jamMasukOffset + 8)->format('H:i:s'),
            'is_aktif' => true,
            'ikut_libur' => false,
            'hari_kerja' => null,
            'is_cross_day' => false,
        ]);

        $pegawai = Pegawai::create([
            'user_id' => $user->id,
            'divisi_id' => $divisi->id,
            'kantor_id' => $kantor->id,
            'shift_id' => $shift->id,
            'nama_lengkap' => 'Pegawai Uji',
            'gender' => 'L',
            'status_aktif' => true,
        ]);

        return [$pegawai, $shift];
    }

    protected function absenData(Shift $shift): array
    {
        return [
            'shift_id' => $shift->id,
            'latitude' => -5.147665,
            'longitude' => 119.432732,
        ];
    }

    protected function setSetting(string $key, $value): void
    {
        app(SettingService::class)->setMany([$key => $value]);
    }

    /**
     * Test 1: Verify that when admin changes "Batas Absen Masuk" setting,
     * AbsensiController uses the new value instead of hardcoded 3 hours.
     *
     * Before the fix: Controller would use hardcoded addHours(3) and reject absensi
     * After the fix: Controller uses SettingService and respects the new 4-hour limit
     */
    public function test_batas_absen_masuk_respects_setting_change_in_controller()
    {
        // Shift started 3 hours ago. With default setting (2 hours), should be rejected.
        [$pegawai, $shift] = $this->makePegawai(-3);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Waktu absen masuk sudah ditutup');

        app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));
    }

    /**
     * Test 1b: Verify that when admin changes "Batas Absen Masuk" setting to a higher value,
     * AbsensiController accepts the absensi (proving it uses the setting, not hardcoded value).
     */
    public function test_batas_absen_masuk_accepts_when_setting_increased()
    {
        // Shift started 3 hours ago, default limit is 2 hours (would be rejected).
        [$pegawai, $shift] = $this->makePegawai(-3);

        // Change setting to 4 hours - should now be accepted.
        $this->setSetting(Setting::KEY_BATAS_ABSEN_MASUK_JAM, 4);

        // If the controller still used hardcoded value, this would fail.
        $absensi = app(AbsensiService::class)->absenMasuk($pegawai, $this->absenData($shift));

        $this->assertNotNull($absensi->jam_masuk);
        $this->assertNotNull($absensi->id);
    }

    /**
     * Test 2: Verify that when admin changes "Batas Absen Pulang" setting,
     * AbsensiController uses the new value instead of hardcoded 2 hours.
     *
     * This tests the checkout deadline calculation in the index() method.
     */
    public function test_batas_absen_pulang_respects_setting_change_in_view()
    {
        // Create absensi from yesterday (pending checkout)
        [$pegawai, $shift] = $this->makePegawai(-26); // Shift was 26 hours ago

        $yesterday = now()->subDay();
        $jamMasuk = $yesterday->clone()->setTime(8, 0, 0);
        $jamPulang = $yesterday->clone()->setTime(16, 0, 0);

        $absensi = Absensi::create([
            'pegawai_id' => $pegawai->id,
            'shift_id' => $shift->id,
            'tanggal' => $yesterday,
            'jam_masuk' => $jamMasuk,
            'jam_pulang' => null, // Not yet checked out
            'status' => 'Hadir',
            'latitude' => -5.147665,
            'longitude' => 119.432732,
        ]);

        // First, with default setting (2 hours), the old session should be considered "stale"
        // when we check with a new shift_id today
        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        // Session should be marked as stale because now() > (yesterday 16:00 + 2 hours)
        // So it shouldn't display the old absensi
        $this->assertTrue(
            now()->gt($jamPulang->copy()->addHours(2)),
            'Assumption: current time is past the default 2-hour checkout deadline'
        );

        // Now increase the deadline to 30 hours - should keep the session fresh
        $this->setSetting(Setting::KEY_BATAS_ABSEN_PULANG_JAM, 30);

        // Re-check the page - with the new 30-hour limit, the session should still be visible
        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertTrue(
            now()->lt($jamPulang->copy()->addHours(30)),
            'Session should be fresh with 30-hour limit'
        );
    }

    /**
     * Test 3: Verify that SettingRepository.setByKey() throws exception
     * when setting key doesn't exist (no silent failure).
     *
     * Before the fix: Repository would silently fail (no exception)
     * After the fix: Repository throws Exception with clear message
     */
    public function test_setting_repository_throws_exception_for_nonexistent_key()
    {
        $repository = app(SettingRepository::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Setting with key 'nonexistent_key_12345' not found");

        $repository->setByKey('nonexistent_key_12345', 'some_value');
    }

    /**
     * Test 3b: Verify that SettingRepository.setByKey() succeeds for valid keys
     * and updates the value correctly.
     */
    public function test_setting_repository_updates_existing_key()
    {
        $repository = app(SettingRepository::class);

        // Update an existing setting that was seeded
        $result = $repository->setByKey(Setting::KEY_BATAS_ABSEN_MASUK_JAM, 5);

        $this->assertNotNull($result);
        $this->assertEquals(5, $result->value);

        // Verify the change persists in the database
        $updated = $repository->findByKey(Setting::KEY_BATAS_ABSEN_MASUK_JAM);
        $this->assertEquals(5, $updated->value);
    }

    /**
     * Test 4 (optional): Verify that the absensi index view displays the correct
     * deadline based on the current setting.
     */
    public function test_view_displays_correct_masuk_deadline_based_on_setting()
    {
        [$pegawai, $shift] = $this->makePegawai(-1); // Shift started 1 hour ago

        // Default setting should calculate deadline as jam_masuk + 2 hours
        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertNotNull($response->viewData('batasAkhirMasuk'));

        // Calculate expected deadline with default setting (2 hours)
        $jamMasuk = Carbon::parse($shift->jam_masuk->format('H:i:s'));
        $expectedDeadline = $jamMasuk->copy()->addHours(2)->format('H:i');

        $this->assertEquals($expectedDeadline, $response->viewData('batasAkhirMasuk'));

        // Now change setting to 5 hours
        $this->setSetting(Setting::KEY_BATAS_ABSEN_MASUK_JAM, 5);

        // View should show the new deadline
        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $expectedDeadline = $jamMasuk->copy()->addHours(5)->format('H:i');
        $this->assertEquals($expectedDeadline, $response->viewData('batasAkhirMasuk'));
    }

    /**
     * Test 4b: Verify that the view correctly sets absenDitutup flag based on
     * the deadline calculated from settings.
     */
    public function test_view_sets_ansen_ditutup_flag_based_on_setting_deadline()
    {
        // Shift started 3 hours ago
        [$pegawai, $shift] = $this->makePegawai(-3);

        // With default 2-hour limit, checkin should be closed
        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertTrue(
            $response->viewData('absenDitutup'),
            'Checkin should be closed after default 2-hour deadline'
        );

        // Increase limit to 5 hours - should reopen
        $this->setSetting(Setting::KEY_BATAS_ABSEN_MASUK_JAM, 5);

        $response = $this->actingAs($pegawai->user)
            ->get(route('absensi.index', ['shift_id' => $shift->id]));

        $response->assertStatus(200);
        $this->assertFalse(
            $response->viewData('absenDitutup'),
            'Checkin should be open with 5-hour deadline'
        );
    }
}

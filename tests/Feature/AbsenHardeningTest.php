<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Divisi;
use App\Models\Kantor;
use App\Models\Pegawai;
use App\Models\Shift;
use App\Models\User;
use App\Services\AbsensiService;
use App\Services\FileUploadService;
use App\Services\SettingService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Database\Seeders\SettingSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class AbsenHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Pegawai $pegawai;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-10-14 12:00:00'));
        Storage::fake('public');

        $this->seed(SettingSeeder::class);
        app(SettingService::class)->flush();

        $divisi = Divisi::create(['nama' => 'Operasional', 'is_aktif' => true]);
        $kantor = Kantor::create([
            'nama' => 'Kantor Pusat', 'titik_lokasi' => '-5.147665,119.432732',
            'radius_meter' => 100, 'is_aktif' => true,
        ]);
        $this->shift = Shift::create([
            'divisi_id' => $divisi->id, 'nama' => 'Shift Pagi',
            'jam_masuk' => '11:00:00', 'jam_pulang' => '19:00:00',
            'is_aktif' => true, 'ikut_libur' => false, 'hari_kerja' => null, 'is_cross_day' => false,
        ]);
        $this->user = User::factory()->create();
        $this->pegawai = Pegawai::create([
            'user_id' => $this->user->id, 'divisi_id' => $divisi->id, 'kantor_id' => $kantor->id,
            'shift_id' => $this->shift->id, 'nama_lengkap' => 'Pegawai Uji', 'gender' => 'L',
            'status_aktif' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Absensi::flushEventListeners();
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function mockTelegram(int $masuk, int $pulang): void
    {
        $this->mock(TelegramService::class, function (MockInterface $mock) use ($masuk, $pulang) {
            $mock->shouldReceive('notifyAbsenMasuk')->times($masuk);
            $mock->shouldReceive('notifyAbsenPulang')->times($pulang);
        });
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'foto' => UploadedFile::fake()->image('foto.jpg', 640, 480),
            'latitude' => -5.147665,
            'longitude' => 119.432732,
            'shift_id' => $this->shift->id,
        ];
    }

    public function test_nama_file_foto_unik_walau_nama_asli_dan_detik_sama(): void
    {
        $service = app(FileUploadService::class);

        $a = $service->upload(UploadedFile::fake()->image('foto.jpg', 64, 64), 'absensi/masuk');
        $b = $service->upload(UploadedFile::fake()->image('foto.jpg', 64, 64), 'absensi/masuk');

        $this->assertNotSame($a->path, $b->path);
        $this->assertCount(2, Storage::disk('public')->allFiles('absensi/masuk'));
    }

    public function test_absen_masuk_ganda_ditolak_sebelum_foto_diproses_dan_telegram_sekali(): void
    {
        $this->mockTelegram(1, 0);

        $this->actingAs($this->user)->postJson(route('absensi.masuk'), $this->payload())->assertOk();

        $kedua = $this->actingAs($this->user)->postJson(route('absensi.masuk'), $this->payload());

        $kedua->assertStatus(400);
        $this->assertStringContainsString('sudah absen masuk', $kedua->json('message'));
        $this->assertCount(1, Storage::disk('public')->allFiles('absensi/masuk'));
        $this->assertSame(1, Absensi::count());
    }

    public function test_absen_pulang_ganda_ditolak_sebelum_foto_diproses_dan_telegram_sekali(): void
    {
        $this->mockTelegram(1, 1);

        $this->actingAs($this->user)->postJson(route('absensi.masuk'), $this->payload())->assertOk();

        $this->actingAs($this->user)
            ->postJson(route('absensi.pulang'), $this->payload(['keterangan' => 'Urusan keluarga']))
            ->assertOk();

        $kedua = $this->actingAs($this->user)
            ->postJson(route('absensi.pulang'), $this->payload(['keterangan' => 'Urusan keluarga']));

        $kedua->assertStatus(400);
        $this->assertCount(1, Storage::disk('public')->allFiles('absensi/pulang'));
    }

    public function test_foto_dihapus_bila_penyimpanan_absensi_gagal(): void
    {
        $this->mockTelegram(0, 0);

        Absensi::creating(function () {
            throw new \RuntimeException('simulasi gagal simpan');
        });

        $this->actingAs($this->user)
            ->postJson(route('absensi.masuk'), $this->payload())
            ->assertStatus(400);

        $this->assertSame([], Storage::disk('public')->allFiles('absensi/masuk'));
        $this->assertSame(0, Absensi::count());
    }

    public function test_pelanggaran_unique_diubah_jadi_pesan_ramah(): void
    {
        $service = app(AbsensiService::class);
        $method = new \ReflectionMethod($service, 'denganKunciPegawai');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Absen Anda sudah tercatat. Silakan muat ulang halaman.');

        $method->invoke($service, $this->pegawai, function () {
            throw new UniqueConstraintViolationException('sqlite', 'insert into absensis', [], new \Exception('duplikat'));
        });
    }
}

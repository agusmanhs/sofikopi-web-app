<?php

namespace App\Services;

use App\Interfaces\Repositories\JadwalKerjaRepositoryInterface;
use App\Models\JadwalOverride;
use App\Models\Pegawai;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JadwalKerjaService extends BaseService
{
    public function __construct(JadwalKerjaRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Ambil pegawai (opsional per divisi) beserta pola jadwal mingguannya, untuk grid admin.
     */
    public function getGridData(?int $divisiId = null)
    {
        return $this->getRepository()->getPegawaiWithPattern($divisiId);
    }

    /**
     * Daftar override yang akan datang (>= hari ini), untuk ditampilkan di panel admin.
     */
    public function getUpcomingOverrides(int $limit = 30)
    {
        return JadwalOverride::with(['pegawai', 'shift'])
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->limit($limit)
            ->get();
    }

    /**
     * Resolusi shift terjadwal seorang pegawai pada tanggal tertentu.
     *
     * Urutan presedensi: override per-tanggal > pola mingguan > tidak ada jadwal.
     * Jadwal (baik override maupun pola) di-key pada tanggal MULAI shift, mengikuti
     * konvensi time-window absen masuk yang sudah ada (lihat AbsensiService::absenMasuk).
     *
     * @return array{scheduled: bool, shift: ?Shift}
     */
    public function resolveShiftFor(Pegawai $pegawai, Carbon $tanggal): array
    {
        $override = JadwalOverride::where('pegawai_id', $pegawai->id)
            ->whereDate('tanggal', $tanggal->toDateString())
            ->first();

        if ($override) {
            return [
                'scheduled' => true,
                'shift' => $override->shift_id ? Shift::find($override->shift_id) : null,
            ];
        }

        $pattern = $pegawai->jadwalKerjas()
            ->where('day_of_week', $tanggal->dayOfWeek)
            ->first();

        if ($pattern) {
            return [
                'scheduled' => true,
                'shift' => $pattern->shift_id ? Shift::find($pattern->shift_id) : null,
            ];
        }

        return [
            'scheduled' => false,
            'shift' => null,
        ];
    }

    /**
     * Simpan pola mingguan (0=Minggu .. 6=Sabtu) untuk satu pegawai sekaligus.
     * Idempotent: memakai updateOrCreate per hari di dalam transaction.
     *
     * @param  array<int, int|null>  $days  [day_of_week => shift_id|null]
     */
    public function savePatternForPegawai(int $pegawaiId, array $days): void
    {
        DB::transaction(function () use ($pegawaiId, $days) {
            foreach ($days as $dayOfWeek => $shiftId) {
                $this->getRepository()->upsertDay(
                    $pegawaiId,
                    (int) $dayOfWeek,
                    $shiftId !== null && $shiftId !== '' ? (int) $shiftId : null
                );
            }
        });
    }

    /**
     * Simpan (buat/perbarui) override jadwal untuk satu pegawai pada satu tanggal.
     * Idempotent lewat updateOrCreate pada unique key (pegawai_id, tanggal).
     */
    public function saveOverride(array $data): JadwalOverride
    {
        $shiftId = $data['shift_id'] ?? null;

        return JadwalOverride::updateOrCreate(
            [
                'pegawai_id' => $data['pegawai_id'],
                'tanggal' => $data['tanggal'],
            ],
            [
                'shift_id' => $shiftId !== '' ? $shiftId : null,
                'keterangan' => $data['keterangan'] ?? null,
            ]
        );
    }

    /**
     * Hapus override jadwal.
     */
    public function deleteOverride(int $id): bool
    {
        $override = JadwalOverride::findOrFail($id);

        return (bool) $override->delete();
    }
}

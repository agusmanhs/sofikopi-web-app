<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\Izin;
use App\Services\IzinService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupAbsensiIzin extends Command
{
    protected $signature = 'absensi:sinkron-izin {--dry-run : Tampilkan ringkasan tanpa menulis perubahan}';

    protected $description = 'Rapikan record absensi yang bentrok dengan izin/cuti yang sudah di-approve (kosongkan jam, set status izin, hapus duplikat)';

    public function __construct(protected IzinService $izinService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $fixed = 0;
        $deleted = 0;

        if ($dryRun) {
            $this->warn('Mode DRY-RUN: tidak ada data yang diubah.');
        }

        Izin::approved()->with(['jenisIzin', 'pegawai'])->chunkById(100, function ($izins) use ($dryRun, &$fixed, &$deleted) {
            foreach ($izins as $izin) {
                $status = $this->izinService->resolveStatusFromJenis($izin->jenisIzin);
                $current = Carbon::parse($izin->tgl_mulai);
                $end = Carbon::parse($izin->tgl_selesai);

                while ($current->lte($end)) {
                    $result = $this->rapikanTanggal($izin, $current->toDateString(), $status, $dryRun);
                    $fixed += $result['fixed'];
                    $deleted += $result['deleted'];
                    $current->addDay();
                }
            }
        });

        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Selesai. Record dirapikan: {$fixed}, duplikat dihapus: {$deleted}.");

        return self::SUCCESS;
    }

    /**
     * Rapikan satu tanggal izin untuk satu pegawai.
     *
     * @return array{fixed:int,deleted:int}
     */
    protected function rapikanTanggal(Izin $izin, string $tanggal, string $status, bool $dryRun): array
    {
        $rows = Absensi::where('pegawai_id', $izin->pegawai_id)
            ->whereDate('tanggal', $tanggal)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return ['fixed' => 0, 'deleted' => 0];
        }

        $keep = $rows->first();
        $duplicates = $rows->slice(1);

        // Apakah perlu dirapikan? (status salah, atau jam masih terisi, atau ada duplikat)
        $perluFix = $keep->status !== $status
            || ! is_null($keep->jam_masuk)
            || ! is_null($keep->jam_pulang);
        $perluDelete = $duplicates->isNotEmpty();

        if (! $perluFix && ! $perluDelete) {
            return ['fixed' => 0, 'deleted' => 0];
        }

        if ($dryRun) {
            return ['fixed' => $perluFix ? 1 : 0, 'deleted' => $duplicates->count()];
        }

        DB::transaction(function () use ($keep, $duplicates, $izin, $status) {
            foreach ($duplicates as $dup) {
                $dup->delete();
            }

            $keep->update([
                'shift_id' => $keep->shift_id ?? $izin->pegawai->shift_id,
                'status' => $status,
                'jam_masuk' => null,
                'jam_pulang' => null,
                'foto_masuk' => null,
                'foto_pulang' => null,
                'latitude_masuk' => null,
                'longitude_masuk' => null,
                'latitude_pulang' => null,
                'longitude_pulang' => null,
                'lokasi_masuk' => null,
                'lokasi_pulang' => null,
                'device_masuk' => null,
                'device_pulang' => null,
                'keterangan' => "Izin: {$izin->jenisIzin->nama} - {$izin->alasan} (disinkronkan oleh sistem)",
            ]);
        });

        return ['fixed' => $perluFix ? 1 : 0, 'deleted' => $duplicates->count()];
    }
}

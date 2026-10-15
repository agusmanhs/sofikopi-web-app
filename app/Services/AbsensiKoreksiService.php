<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\ActivityLog;
use App\Models\Izin;
use App\Models\Pegawai;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AbsensiKoreksiService
{
    private const EDITABLE = ['jam_masuk', 'jam_pulang', 'status', 'keterangan'];

    private const EDITABLE_STATUS = ['Tepat Waktu', 'Terlambat', 'Hadir'];

    public function ubah(Absensi $absensi, array $data, User $actor): Absensi
    {
        $this->assertSuperAdmin($actor);

        [$fresh, $old, $pegawai, $tanggal] = DB::transaction(function () use ($absensi, $data, $actor) {
            $locked = Absensi::lockForUpdate()->findOrFail($absensi->id);

            if (! in_array($locked->status, self::EDITABLE_STATUS, true)) {
                throw new \DomainException('Absensi bertipe izin/cuti tidak dapat dikoreksi di sini, gunakan alur Izin.');
            }

            $pegawai = $this->activePegawai($locked->pegawai_id);
            $tanggal = $locked->tanggal->format('Y-m-d');
            $this->assertTidakSedangIzin($pegawai->id, $tanggal);

            $old = $this->snapshot($locked);
            $merged = array_merge($old, $this->normalize(array_intersect_key($data, array_flip(self::EDITABLE))));
            $this->assertUrutanJam($merged, (bool) $locked->shift?->is_cross_day);
            $this->assertTidakMelebihiSekarang($merged, $tanggal);

            $locked->fill($merged + [
                'dikoreksi_oleh' => $actor->id,
                'alasan_koreksi' => $data['alasan'],
            ])->save();

            $fresh = $locked->fresh();
            $this->audit('koreksi', $fresh, $old, $this->snapshot($fresh), $data['alasan'], $actor, $pegawai, $tanggal);

            return [$fresh, $old, $pegawai, $tanggal];
        });

        $this->notifyTelegram('ubah', $actor, $pegawai, $tanggal, $old, $this->snapshot($fresh), $data['alasan']);

        return $fresh;
    }

    public function tambah(array $data, User $actor): Absensi
    {
        $this->assertSuperAdmin($actor);

        $tanggal = Carbon::parse($data['tanggal'])->format('Y-m-d');

        if ($tanggal > today()->format('Y-m-d')) {
            throw new \DomainException('Tanggal tidak boleh di masa depan.');
        }

        [$absensi, $pegawai] = DB::transaction(function () use ($data, $actor, $tanggal) {
            Pegawai::lockForUpdate()->findOrFail($data['pegawai_id']);
            $pegawai = $this->activePegawai($data['pegawai_id']);

            $shift = Shift::aktif()->find($data['shift_id'] ?? null);
            if (! $shift || (int) $shift->divisi_id !== (int) $pegawai->divisi_id) {
                throw new \DomainException('Shift tidak valid untuk divisi pegawai.');
            }

            $this->assertTidakSedangIzin($pegawai->id, $tanggal);

            $exists = Absensi::where('pegawai_id', $pegawai->id)
                ->whereDate('tanggal', $tanggal)
                ->where(fn ($q) => $q->where('shift_id', $shift->id)->orWhereNull('shift_id'))
                ->exists();
            if ($exists) {
                throw new \DomainException('Absensi untuk pegawai, tanggal, dan shift ini sudah ada. Gunakan ubah.');
            }

            $values = $this->normalize(array_merge(
                ['jam_masuk' => null, 'jam_pulang' => null, 'status' => null, 'keterangan' => null],
                array_intersect_key($data, array_flip(self::EDITABLE))
            ));
            $this->assertUrutanJam($values, (bool) $shift->is_cross_day);
            $this->assertTidakMelebihiSekarang($values, $tanggal);

            $absensi = Absensi::create($values + [
                'pegawai_id' => $pegawai->id,
                'shift_id' => $shift->id,
                'tanggal' => $tanggal,
                'dikoreksi_oleh' => $actor->id,
                'alasan_koreksi' => $data['alasan'],
            ]);

            $this->audit('koreksi_tambah', $absensi, null, $this->snapshot($absensi->fresh()), $data['alasan'], $actor, $pegawai, $tanggal);

            return [$absensi, $pegawai];
        });

        $this->notifyTelegram('tambah', $actor, $pegawai, $tanggal, null, $this->snapshot($absensi->fresh()), $data['alasan']);

        return $absensi;
    }

    private function assertTidakMelebihiSekarang(array $values, string $tanggal): void
    {
        if ($tanggal !== today()->format('Y-m-d')) {
            return;
        }

        $now = now()->format('H:i:s');
        foreach (['jam_masuk' => 'Jam masuk', 'jam_pulang' => 'Jam pulang'] as $key => $label) {
            if (! empty($values[$key]) && Carbon::parse($values[$key])->format('H:i:s') > $now) {
                throw new \DomainException("{$label} tidak boleh lebih dari jam sekarang untuk tanggal hari ini.");
            }
        }
    }

    private function assertSuperAdmin(User $actor): void
    {
        if (! $actor->role || $actor->role->slug !== 'super-admin') {
            abort(403, 'Akses ditolak. Fitur ini hanya untuk super admin.');
        }
    }

    private function activePegawai(int|string $pegawaiId): Pegawai
    {
        $pegawai = Pegawai::aktif()->find($pegawaiId);
        if (! $pegawai) {
            throw new \DomainException('Pegawai tidak ditemukan atau tidak aktif.');
        }

        return $pegawai;
    }

    private function assertTidakSedangIzin(int $pegawaiId, string $tanggal): void
    {
        if (Izin::where('pegawai_id', $pegawaiId)->approvedOn($tanggal)->exists()) {
            throw new \DomainException('Pegawai memiliki izin/cuti disetujui pada tanggal ini, ubah lewat alur izin.');
        }
    }

    private function assertUrutanJam(array $values, bool $isCrossDay): void
    {
        if ($isCrossDay || empty($values['jam_masuk']) || empty($values['jam_pulang'])) {
            return;
        }

        if (Carbon::parse($values['jam_pulang'])->lt(Carbon::parse($values['jam_masuk']))) {
            throw new \DomainException('Jam pulang tidak boleh lebih awal dari jam masuk.');
        }
    }

    private function normalize(array $values): array
    {
        foreach (['jam_masuk', 'jam_pulang'] as $key) {
            if (array_key_exists($key, $values) && $values[$key]) {
                $values[$key] = Carbon::parse($values[$key])->format('H:i:s');
            }
        }

        return $values;
    }

    private function snapshot(Absensi $absensi): array
    {
        return [
            'jam_masuk' => $absensi->jam_masuk?->format('H:i:s'),
            'jam_pulang' => $absensi->jam_pulang?->format('H:i:s'),
            'status' => $absensi->status,
            'keterangan' => $absensi->keterangan,
        ];
    }

    private function audit(string $action, Absensi $absensi, ?array $old, array $new, string $alasan, User $actor, Pegawai $pegawai, string $tanggal): void
    {
        $aksi = $action === 'koreksi' ? 'mengubah' : 'menambah';

        ActivityLog::create([
            'user_id' => $actor->id,
            'user_name' => $actor->pegawai?->nama_lengkap ?? $actor->name,
            'action' => $action,
            'module' => 'absensi',
            'description' => "Koreksi absensi: {$aksi} absensi {$pegawai->nama_lengkap} tanggal {$tanggal}. Alasan: {$alasan}",
            'subject_type' => Absensi::class,
            'subject_id' => $absensi->id,
            'old_values' => $old,
            'new_values' => $new,
            'properties' => [
                'alasan' => $alasan,
                'pegawai_id' => $pegawai->id,
                'tanggal' => $tanggal,
                'shift_id' => $absensi->shift_id,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function notifyTelegram(string $aksi, User $actor, Pegawai $pegawai, string $tanggal, ?array $old, array $new, string $alasan): void
    {
        $chatId = trim((string) config('services.telegram.backup_chat_id', ''));
        if ($chatId === '') {
            return;
        }

        try {
            $e = fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES);

            app(TelegramService::class)->notify('KOREKSI ABSENSI', [
                'Oleh' => $e($actor->pegawai?->nama_lengkap ?? $actor->name),
                'Pegawai' => $e($pegawai->nama_lengkap),
                'Tanggal' => $e($tanggal),
                'Aksi' => $e($aksi),
                'Sebelum' => $old ? $e($this->ringkas($old)) : '-',
                'Sesudah' => $e($this->ringkas($new)),
                'Alasan' => $e($alasan),
            ], '✏️', null, $chatId);
        } catch (\Throwable $ex) {
            Log::warning('Notifikasi Telegram koreksi absensi gagal: '.$ex->getMessage());
        }
    }

    private function ringkas(array $values): string
    {
        return sprintf(
            'Masuk %s, Pulang %s, Status %s, Ket %s',
            $values['jam_masuk'] ?? '-',
            $values['jam_pulang'] ?? '-',
            $values['status'] ?? '-',
            $values['keterangan'] ?? '-'
        );
    }
}

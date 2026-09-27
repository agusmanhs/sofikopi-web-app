<?php

namespace App\Services;

use App\Models\Setting;
use App\Repositories\SettingRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingService extends BaseService
{
    protected const CACHE_KEY = 'app_settings_map';

    protected const CACHE_TTL = 3600;

    /**
     * Nilai cadangan bila tabel/baris setting belum ada (fresh install,
     * migration belum jalan). Menjaga absensi tetap berfungsi.
     */
    protected const DEFAULTS = [
        Setting::KEY_BATAS_ABSEN_DIBUKA_JAM => 2,
        Setting::KEY_BATAS_ABSEN_MASUK_JAM => 2,
        Setting::KEY_BATAS_ABSEN_PULANG_JAM => 2,
        Setting::KEY_BATAS_IZIN_JAM => 1,
    ];

    public function __construct(SettingRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Ambil nilai setting (sudah di-cast sesuai kolom type).
     */
    public function get(string $key, $default = null)
    {
        $map = $this->map();

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        return $default ?? self::DEFAULTS[$key] ?? null;
    }

    /**
     * Ambil nilai setting sebagai integer positif, jatuh ke default bila
     * kosong/nol/negatif. Dipakai untuk batas jam absensi & izin.
     */
    public function getJam(string $key): int
    {
        $value = (int) $this->get($key);

        if ($value <= 0) {
            return (int) (self::DEFAULTS[$key] ?? 0);
        }

        return $value;
    }

    /**
     * Ambil koleksi Setting untuk satu group (untuk render form).
     */
    public function getByGroup(string $group)
    {
        if (! $this->tableExists()) {
            return collect();
        }

        return $this->repository->getByGroup($group);
    }

    /**
     * Simpan banyak setting sekaligus: [key => value].
     */
    public function setMany(array $values): void
    {
        if (! $this->tableExists()) {
            return;
        }

        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                $this->repository->setByKey($key, $value);
            }
        });

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Peta key => nilai ter-cast, di-cache agar tidak query per pemakaian.
     *
     * @return array<string, mixed>
     */
    protected function map(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            if (! $this->tableExists()) {
                return [];
            }

            return Setting::all()
                ->mapWithKeys(fn (Setting $setting) => [
                    $setting->key => $setting->casted_value,
                ])
                ->all();
        });
    }

    protected function tableExists(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

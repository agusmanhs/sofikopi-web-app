<?php

namespace App\Services;

use App\Models\HariLibur;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HariLiburService
{
    /**
     * Sync hari libur from external API
     * Source: https://api-harilibur.vercel.app/api
     */
    public function syncFromApi($year = null)
    {
        $year = $year ?? now()->year;
        $url = "https://api-harilibur.vercel.app/api?year={$year}";

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->failed()) {
                throw new \Exception("Gagal menghubungi API Hari Libur.");
            }

            $data = $response->json();

            $count = 0;
            foreach ($data as $item) {
                // API format check: usually has 'holiday_date', 'holiday_name', 'is_national_holiday'
                if (!isset($item['holiday_date'])) continue;

                HariLibur::updateOrCreate(
                    ['tanggal' => $item['holiday_date']],
                    [
                        'nama' => $item['holiday_name'],
                        'is_nasional' => $item['is_national_holiday'] ?? true,
                        // Default API doesn't specify cuti bersama explicitly in boolean, so we default to false or infer from name if needed
                        'is_cuti_bersama' => str_contains(strtolower($item['holiday_name']), 'cuti bersama'),
                        'deskripsi' => 'Disinkronisasi otomatis dari API',
                    ]
                );
                $count++;
            }

            return [
                'success' => true, 
                'message' => "Berhasil menyinkronkan {$count} hari libur untuk tahun {$year}."
            ];

        } catch (\Exception $e) {
            Log::error("HariLibur Sync Error: " . $e->getMessage());
            return [
                'success' => false, 
                'message' => "Terjadi kesalahan: " . $e->getMessage()
            ];
        }
    }

    public function getAll($year = null)
    {
        $query = HariLibur::query();
        if ($year) {
            $query->whereYear('tanggal', $year);
        }
        return $query->orderBy('tanggal')->get();
    }

    public function getPaginated($year = null, $perPage = 10)
    {
        $query = HariLibur::query();
        if ($year) {
            $query->whereYear('tanggal', $year);
        }
        return $query->orderBy('tanggal')->paginate($perPage);
    }

    public function create(array $data)
    {
        return HariLibur::create($data);
    }

    public function find($id)
    {
        return HariLibur::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $item = $this->find($id);
        $item->update($data);
        return $item;
    }

    public function delete($id)
    {
        $item = $this->find($id);
        $item->delete();
        return true;
    }
}
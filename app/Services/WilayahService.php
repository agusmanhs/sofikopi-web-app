<?php

namespace App\Services;

use App\Models\Province;
use App\Models\Regency;
use App\Models\District;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WilayahService
{
    protected $baseUrl = 'https://wilayah.id/api';

    public function syncProvinces()
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/provinces.json");

            if ($response->failed()) {
                throw new \Exception("Gagal menghubungi API Wilayah (Provinces).");
            }

            $data = $response->json();
            $count = 0;

            foreach ($data['data'] as $item) {
                Province::updateOrCreate(
                    ['code' => $item['code']],
                    ['name' => $item['name']]
                );
                $count++;
            }

            return ['success' => true, 'message' => "Berhasil menyinkronkan {$count} provinsi."];
        } catch (\Exception $e) {
            Log::error("Wilayah Sync (Provinces) Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function syncRegencies($provinceCode)
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/regencies/{$provinceCode}.json");

            if ($response->failed()) {
                throw new \Exception("Gagal menghubungi API Wilayah (Regencies for {$provinceCode}).");
            }

            $data = $response->json();
            $count = 0;

            foreach ($data['data'] as $item) {
                Regency::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'province_code' => $provinceCode,
                        'name' => $item['name'],
                    ]
                );
                $count++;
            }

            return ['success' => true, 'message' => "Berhasil menyinkronkan {$count} kabupaten/kota."];
        } catch (\Exception $e) {
            Log::error("Wilayah Sync (Regencies) Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function syncDistricts($regencyCode)
    {
        try {
            $response = Http::timeout(10)->get("{$this->baseUrl}/districts/{$regencyCode}.json");

            if ($response->failed()) {
                throw new \Exception("Gagal menghubungi API Wilayah (Districts for {$regencyCode}).");
            }

            $data = $response->json();
            $count = 0;

            foreach ($data['data'] as $item) {
                District::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'regency_code' => $regencyCode,
                        'name' => $item['name'],
                    ]
                );
                $count++;
            }

            return ['success' => true, 'message' => "Berhasil menyinkronkan {$count} kecamatan."];
        } catch (\Exception $e) {
            Log::error("Wilayah Sync (Districts) Error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

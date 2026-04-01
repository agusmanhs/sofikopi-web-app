<?php

namespace App\Imports;

use App\Models\Mitra;
use App\Models\MitraCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class MitraImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip if name is empty
        if (empty($row['nama_mitra'])) {
            return null;
        }

        // 1. Handle Kategori (Otomatis isi & Format Capitalize)
        $categoryName = $this->formatString($row['kategori'] ?? 'Umum');
        $category = MitraCategory::firstOrCreate(
            ['name' => $categoryName],
            ['is_active' => true]
        );

        // 2. Handle Kode Mitra (Gunakan yang ada atau generate)
        $code = !empty($row['kode_mitra']) ? $row['kode_mitra'] : $this->generateMitraCode();

        // 3. Format strings to Capitalize (Title Case)
        return new Mitra([
            'mitra_category_id' => $category->id,
            'code'              => $code,
            'name'              => $this->formatString($row['nama_mitra']),
            'pic'               => $this->formatString($row['pic'] ?? ''),
            'phone'             => $row['no_hp'] ?? $row['phone'] ?? '',
            'address'           => $this->formatString($row['alamat'] ?? ''),
            'is_active'         => true,
            // Regional & Map dicosongkan sesuai request
            'province_code'     => null,
            'regency_code'      => null,
            'district_code'     => null,
            'latitude'          => null,
            'longitude'         => null,
        ]);
    }

    /**
     * Helper to format string to Capitalize (Title Case)
     */
    private function formatString(?string $string): string
    {
        if (empty($string)) return '';
        return Str::title(strtolower(trim($string)));
    }

    /**
     * Generate unique Mitra Code
     */
    private function generateMitraCode(): string
    {
        $prefix = 'MTR-' . date('Ymd');
        $count = Mitra::where('code', 'like', $prefix . '%')->count();
        return $prefix . Str::padLeft($count + 1, 3, '0');
    }
}

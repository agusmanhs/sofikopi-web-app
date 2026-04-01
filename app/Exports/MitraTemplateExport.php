<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MitraTemplateExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    /**
     * @return array
     */
    public function array(): array
    {
        return [
            [
                'MTR-001',
                'Toko Sembako Maju',
                'Reseller',
                'Bapak Ahmad',
                '081234567890',
                'Jl. Kebon Jeruk No. 12',
            ]
        ];
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Kode Mitra',
            'Nama Mitra',
            'Kategori',
            'PIC',
            'No HP',
            'Alamat',
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Template Import Mitra';
    }
}

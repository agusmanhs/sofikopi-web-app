<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateExport implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        return [
            [
                'PROD-001',
                'Kopi Kenangan',
                'Minuman',
                'Kopi Susu',
                'Kopi susu gula aren botol 250ml',
                '12000',
                '18000',
                '10',
                'Botol'
            ],
            [
                '',
                'Espresso Beans 1kg',
                'Bahan Baku',
                'Coffee Bean',
                'Roasted coffee beans premium blend',
                '150000',
                '250000',
                '5',
                'Bag'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Nama Produk',
            'Kategori',
            'Sub Kategori',
            'Deskripsi',
            'Harga Beli',
            'Harga Jual',
            'Stok Minimal',
            'Satuan'
        ];
    }

    public function title(): string
    {
        return 'Template Import Produk';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

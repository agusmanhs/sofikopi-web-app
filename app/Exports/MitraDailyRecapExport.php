<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Mirrors the "DATA" sheet layout from the Cafe Lallo Kendari workbook —
 * one row per calendar date. $rows is the array shape produced by
 * MitraReportService::dailyRecap().
 */
class MitraDailyRecapExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected Collection $rows) {}

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Pendapatan Kotor',
            'Diskon',
            'Service Charge',
            'Pajak',
            'Pendapatan Bersih',
            'HPP',
            'COGS',
            'Penerimaan Cash',
            'Penerimaan QRIS',
            'Penerimaan Transfer',
            'Penerimaan EDC',
            'Potongan Administrasi',
            'Penerimaan Bersih',
            'Total Void',
            'Jumlah Void',
            'Jumlah Transaksi',
        ];
    }

    public function map($row): array
    {
        return [
            $row['tanggal']->format('d/m/Y'),
            $row['pendapatan_kotor'],
            $row['diskon'],
            $row['service_charge'],
            $row['pajak'],
            $row['pendapatan_bersih'],
            $row['hpp'],
            $row['cogs'],
            $row['penerimaan_cash'],
            $row['penerimaan_qris'],
            $row['penerimaan_transfer'],
            $row['penerimaan_edc'],
            $row['potongan_admin'],
            $row['penerimaan_bersih'],
            $row['void_total'],
            $row['void_count'],
            $row['jumlah_transaksi'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

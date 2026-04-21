<?php

namespace App\Exports;

use App\Models\Kunjungan;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KunjunganExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = Kunjungan::with(['user.pegawai', 'mitra'])->orderBy('tanggal_kunjungan', 'desc');

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('tanggal_kunjungan', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('tanggal_kunjungan', '<=', $this->filters['end_date']);
        }

        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (!empty($this->filters['mitra_id'])) {
            $query->where('mitra_id', $this->filters['mitra_id']);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Tipe',
            'Tanggal',
            'Petugas',
            'Outlet / Mitra',
            'Espresso Calibration',
            'Taste Notes',
            'Flow of Customers',
            'Feedback',
            'Problem',
            'Note'
        ];
    }

    public function map($kunjungan): array
    {
        return [
            $kunjungan->id,
            $kunjungan->visit_type == 'routine' ? 'Rutin' : 'By Request',
            $kunjungan->tanggal_kunjungan->format('d/m/Y'),
            $kunjungan->user->pegawai->nama_lengkap ?? $kunjungan->user->name ?? '-',
            $kunjungan->mitra->name ?? '-',
            $kunjungan->espresso_calibration,
            $kunjungan->taste_notes,
            $kunjungan->flow_of_customers,
            $kunjungan->feedback,
            $kunjungan->problem,
            $kunjungan->note,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

<?php

namespace App\Exports;

use App\Models\Kunjungan;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KunjunganExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * FromQuery: Excel membaca per potongan (chunk), tidak memuat seluruh baris sebagai
     * model Eloquent sekaligus seperti FromCollection + get().
     */
    public function query()
    {
        $query = Kunjungan::with(['user.pegawai', 'mitra'])
            ->orderBy('tanggal_kunjungan', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($this->filters['start_date'])) {
            $query->where('tanggal_kunjungan', '>=', Carbon::parse($this->filters['start_date'])->toDateString());
        }

        if (!empty($this->filters['end_date'])) {
            $query->where('tanggal_kunjungan', '<', Carbon::parse($this->filters['end_date'])->addDay()->toDateString());
        }

        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (!empty($this->filters['mitra_id'])) {
            $query->where('mitra_id', $this->filters['mitra_id']);
        }

        return $query;
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

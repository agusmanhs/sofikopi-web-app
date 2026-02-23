<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapAbsensiExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $bulan;
    protected $tahun;
    protected $hariEfektifFull;
    protected $hariEfektifReguler;
    protected $jenisIzins;
    protected $totalLibur;

    public function __construct($data, $bulan, $tahun, $hariEfektifFull, $hariEfektifReguler, $jenisIzins, $totalLibur)
    {
        $this->data = $data;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->hariEfektifFull = $hariEfektifFull;
        $this->hariEfektifReguler = $hariEfektifReguler;
        $this->jenisIzins = $jenisIzins;
        $this->totalLibur = $totalLibur;
    }

    public function view(): View
    {
        // Tetap gunakan template blade yang sudah kita buat karena library ini mendukung rendering dari HTML Blade
        return view('pages.absensi.exports.rekap-excel', [
            'data' => $this->data,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
            'hariEfektifFull' => $this->hariEfektifFull,
            'hariEfektifReguler' => $this->hariEfektifReguler,
            'jenisIzins' => $this->jenisIzins,
            'totalLibur' => $this->totalLibur
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // Kita bisa menambahkan styling tambahan di sini jika diperlukan, 
        // tapi library ini akan mencoba membaca style inline dari Blade.
        return [
            // Style untuk header No dan Nama (row 1 & 2)
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['bold' => true]],
        ];
    }
}

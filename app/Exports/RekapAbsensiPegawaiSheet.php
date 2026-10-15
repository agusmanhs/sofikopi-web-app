<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class RekapAbsensiPegawaiSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function __construct(
        protected $pegawai,
        protected int $bulan,
        protected int $tahun,
        protected string $title
    ) {}

    public function view(): View
    {
        $start = Carbon::createFromDate($this->tahun, $this->bulan, 1)->startOfMonth();
        $absensiByDate = $this->pegawai->absensis->keyBy(fn ($a) => $a->tanggal->format('Y-m-d'));

        $rows = [];
        for ($d = $start->copy(); $d->month === $this->bulan; $d->addDay()) {
            $rows[] = [
                'tanggal' => $d->copy(),
                'absensi' => $absensiByDate->get($d->format('Y-m-d')),
            ];
        }

        return view('pages.absensi.exports.rekap-pegawai-excel', [
            'pegawai' => $this->pegawai,
            'statistik' => $this->pegawai->statistik,
            'rows' => $rows,
            'bulan' => $this->bulan,
            'tahun' => $this->tahun,
        ]);
    }

    public function title(): string
    {
        return $this->title;
    }
}

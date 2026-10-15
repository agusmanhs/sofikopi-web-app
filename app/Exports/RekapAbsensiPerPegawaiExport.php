<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RekapAbsensiPerPegawaiExport implements WithMultipleSheets
{
    public function __construct(
        protected $pegawais,
        protected int $bulan,
        protected int $tahun
    ) {}

    public function sheets(): array
    {
        $usedTitles = [];
        $sheets = [];

        foreach ($this->pegawais as $pegawai) {
            $base = trim(preg_replace('/[\\\\\/\?\*\[\]:]/', '', $pegawai->nama_lengkap)) ?: 'Pegawai';
            $base = mb_substr($base, 0, 31);
            $title = $base;
            $i = 2;

            while (in_array(mb_strtolower($title), $usedTitles)) {
                $suffix = ' '.$i++;
                $title = mb_substr($base, 0, 31 - mb_strlen($suffix)).$suffix;
            }

            $usedTitles[] = mb_strtolower($title);
            $sheets[] = new RekapAbsensiPegawaiSheet($pegawai, $this->bulan, $this->tahun, $title);
        }

        return $sheets;
    }
}

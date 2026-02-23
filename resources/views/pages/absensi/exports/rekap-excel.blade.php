<table>
   <thead>
      <tr>
         <th rowspan="2"
            style="background-color: #f2f2f2; border: 1px solid #000; text-align: center; vertical-align: middle;">No
         </th>
         <th rowspan="2"
            style="background-color: #f2f2f2; border: 1px solid #000; text-align: center; vertical-align: middle;">Nama
         </th>
         <th colspan="{{ 6 + $jenisIzins->count() }}"
            style="background-color: #d9d9d9; border: 1px solid #000; text-align: center;">Kehadiran Hari</th>
         <th colspan="6" style="background-color: #d9d9d9; border: 1px solid #000; text-align: center;">Waktu Kerja
         </th>
      </tr>
      <tr>
         <!-- Kehadiran Hari Sub-columns (Orange) -->
         <th style="background-color: #ffa500; border: 1px solid #000; color: #ffffff; text-align: center;">Hadir</th>
         <th style="background-color: #ffa500; border: 1px solid #000; color: #ffffff; text-align: center;">Absen</th>
         <th style="background-color: #ffa500; border: 1px solid #000; color: #ffffff; text-align: center;">Tepat Waktu
         </th>
         @foreach ($jenisIzins as $j)
            <th style="background-color: #ffa500; border: 1px solid #000; color: #ffffff; text-align: center;">
               {{ $j->nama }}</th>
         @endforeach
         <th style="background-color: #ffa500; border: 1px solid #000; color: #ffffff; text-align: center;">Libur</th>
         <th style="background-color: #ffa500; border: 1px solid #000; color: #ffffff; text-align: center;">Persentase
         </th>
         <th style="background-color: #ffa500; border: 1px solid #000; color: #ffffff; text-align: center;">Scoring</th>

         <!-- Waktu Kerja Sub-columns (Dark Red) -->
         <th style="background-color: #990000; border: 1px solid #000; color: #ffffff; text-align: center;">Denda
            Keterlambatan</th>
         <th style="background-color: #990000; border: 1px solid #000; color: #ffffff; text-align: center;">Jam Masuk
         </th>
         <th style="background-color: #990000; border: 1px solid #000; color: #ffffff; text-align: center;">Terlambat
         </th>
         <th style="background-color: #990000; border: 1px solid #000; color: #ffffff; text-align: center;">Durasi Menit
         </th>
         <th style="background-color: #990000; border: 1px solid #000; color: #ffffff; text-align: center;">Akumulasi
            Kehadiran</th>
         <th style="background-color: #990000; border: 1px solid #000; color: #ffffff; text-align: center;">Akumulasi
            Jam Kerja</th>
      </tr>
   </thead>
   <tbody>
      @foreach ($data as $index => $pegawai)
         @php
            $isReguler = $pegawai->shift && $pegawai->shift->ikut_libur;
            $hariAktifTarget = $isReguler ? $hariEfektifReguler : $hariEfektifFull;

            $absensis = $pegawai->absensis;

            // Hadir: Total unique days present (Tepat Waktu, Hadir, Terlambat, Dinas, or Any with Jam Masuk)
            $hadirCount = $absensis
                ->filter(function ($i) {
                    $isHadir = in_array($i->status, ['Tepat Waktu', 'Hadir', 'Terlambat', 'Dinas Luar Kota', 'Tugas']);
                    $hasClockIn = !is_null($i->jam_masuk);
                    return ($isHadir || $hasClockIn) && (!is_null($i->jam_pulang) || $i->tanggal->isToday());
                })
                ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                ->count();

            // Tepat Waktu / Hadir Murni (Tanpa Telat)
            $tepatWaktuCount = $absensis
                ->filter(function ($i) {
                    if ($i->status === 'Terlambat') {
                        return false;
                    }
                    $isHadir = in_array($i->status, ['Tepat Waktu', 'Hadir', 'Dinas Luar Kota', 'Tugas']);
                    $hasClockIn = !is_null($i->jam_masuk);
                    return ($isHadir || $hasClockIn) && (!is_null($i->jam_pulang) || $i->tanggal->isToday());
                })
                ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                ->count();

            // Telat: Total sessions with Terlambat status
            $terlambatCount = $absensis
                ->filter(fn($i) => $i->status === 'Terlambat' && (!is_null($i->jam_pulang) || $i->tanggal->isToday()))
                ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                ->count();

            // Izin counts per type
            $izinCounts = [];
            foreach ($jenisIzins as $j) {
                $izinCounts[$j->nama] = $absensis
                    ->where('status', $j->nama)
                    ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                    ->count();
            }

            // Total unique days covered (Hadir + any Izin/Sakit/Cuti)
            $daysActive = $absensis
                ->filter(function ($item) {
                    if (
                        in_array($item->status, [
                            'Izin',
                            'Sakit',
                            'Cuti',
                            'Izin Pribadi',
                            'Cuti Tahunan',
                            'Dinas Luar Kota',
                        ])
                    ) {
                        return true;
                    }
                    return !is_null($item->jam_pulang) || $item->tanggal->isToday();
                })
                ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                ->count();

            // Alpha (Absen)
            $alphaCount = max(0, $hariAktifTarget - $daysActive);

            // Percentage
            $persentase = $hariAktifTarget > 0 ? round(($daysActive / $hariAktifTarget) * 100, 2) : 0;

            // Duration work
            $totalMenit = $absensis->sum('durasi_kerja_menit');
            $totalJam = floor($totalMenit / 60);
            $sisaMenit = $totalMenit % 60;
            $durasiFormat = "{$totalJam} Jam " . ($sisaMenit > 0 ? "{$sisaMenit} Menit" : '');
         @endphp
         <tr>
            <td style="border: 1px solid #000; text-align: center;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000;">{{ $pegawai->nama_lengkap }}</td>

            <!-- Kehadiran Hari Values -->
            <td style="border: 1px solid #000; text-align: center;">{{ $hadirCount }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $alphaCount }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $tepatWaktuCount }}</td>
            @foreach ($jenisIzins as $j)
               <td style="border: 1px solid #000; text-align: center;">{{ $izinCounts[$j->nama] ?? 0 }}</td>
            @endforeach
            <td style="border: 1px solid #000; text-align: center;">{{ $totalLibur }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $persentase }}%</td>
            <td style="border: 1px solid #000; text-align: center;">0,00</td>

            <!-- Waktu Kerja Values -->
            <td style="border: 1px solid #000; text-align: center;">0</td>
            <td style="border: 1px solid #000; text-align: center;">0</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $terlambatCount }}</td>
            <td style="border: 1px solid #000; text-align: center;">0</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $hadirCount }}</td>
            <td style="border: 1px solid #000; text-align: center;">{{ $durasiFormat }}</td>
         </tr>
      @endforeach
   </tbody>
</table>

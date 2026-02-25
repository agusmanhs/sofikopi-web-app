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

            // Kita bisa pakai properti statistik yang sudah disuntikkan dari controller
            $statistik = $pegawai->statistik ?? [
                'hadir' => 0,
                'tepat_waktu' => 0,
                'terlambat' => 0,
                'izin' => 0,
                'alfa' => $hariAktifTarget,
            ];

            $absensis = $pegawai->absensis ?? collect([]);

            $hadirCount = $statistik['hadir'] ?? 0;
            $tepatWaktuCount = $statistik['tepat_waktu'] ?? 0;
            $terlambatCount = $statistik['terlambat'] ?? 0;
            $alphaCount = $statistik['alfa'] ?? 0;

            // Izin counts per type
            $izinCounts = [];
            foreach ($jenisIzins as $j) {
                $izinCounts[$j->nama] = $absensis
                    ->where('status', $j->nama)
                    ->unique(fn($i) => $i->tanggal->format('Y-m-d'))
                    ->count();
            }

            // Total unique days covered (Hadir + any Izin/Sakit/Cuti)
            $daysActive = $hadirCount + ($statistik['izin'] ?? 0);

            // Percentage
            $persentase =
                $hariAktifTarget > 0 ? round((($hariAktifTarget - $alphaCount) / $hariAktifTarget) * 100, 2) : 0;
            if ($persentase > 100) {
                $persentase = 100;
            }
            if ($persentase < 0) {
                $persentase = 0;
            }

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

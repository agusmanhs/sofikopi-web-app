@php
   $th = 'background-color: #d9d9d9; border: 1px solid #000; text-align: center; font-weight: bold;';
   $td = 'border: 1px solid #000; text-align: center;';
   $merah = 'background-color: #ff0000; color: #ffffff; border: 1px solid #000; text-align: center;';
   $hariId = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
   $bulanId = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
   $namaBulan = $bulanId[$bulan].' '.$tahun;
@endphp
<table>
   <thead>
      <tr>
         <th colspan="7" style="font-weight: bold; text-align: center;">ABSENSI {{ strtoupper($pegawai->nama_lengkap) }} - {{ strtoupper($namaBulan) }}</th>
      </tr>
      <tr>
         <th style="{{ $th }}">Hari</th>
         <th style="{{ $th }}">Tanggal</th>
         <th style="{{ $th }}">Status</th>
         <th style="{{ $th }}">Masuk</th>
         <th style="{{ $th }}">Pulang</th>
         <th style="{{ $th }}">Jam Kerja</th>
         <th style="{{ $th }}">Telat</th>
      </tr>
   </thead>
   <tbody>
      @foreach ($rows as $row)
         @php
            $tgl = $row['tanggal'];
            $a = $row['absensi'];
            $style = $tgl->isSunday() ? $merah : $td;
            if ($a && in_array($a->status, ['Tepat Waktu', 'Hadir', 'Terlambat'])) {
                $statusText = $a->status === 'Terlambat' ? 'TERLAMBAT' : 'HADIR';
            } elseif ($a) {
                $statusText = strtoupper($a->status);
            } else {
                $statusText = '';
            }
         @endphp
         <tr>
            <td style="{{ $style }}">{{ $hariId[$tgl->format('l')] }}</td>
            <td style="{{ $style }}">{{ $tgl->format('d/m/Y') }}</td>
            <td style="{{ $style }}">{{ $statusText }}</td>
            <td style="{{ $style }}">{{ $a && $a->jam_masuk ? $a->jam_masuk->format('H:i') : '' }}</td>
            <td style="{{ $style }}">{{ $a && $a->jam_pulang ? $a->jam_pulang->format('H:i') : '' }}</td>
            <td style="{{ $style }}">{{ $a && $a->jam_masuk && $a->jam_pulang ? trim($a->durasi_kerja) : '' }}</td>
            <td style="{{ $style }}">{{ $a && $a->status === 'Terlambat' ? 'Terlambat' : '' }}</td>
         </tr>
      @endforeach
      <tr>
         <td colspan="2" style="{{ $th }}">HARI KERJA</td>
         <td style="{{ $td }}">{{ $statistik['total_hari_kerja'] }}</td>
         <td colspan="4"></td>
      </tr>
      <tr>
         <td colspan="2" style="{{ $th }}">HADIR</td>
         <td style="{{ $td }}">{{ $statistik['hadir'] }}</td>
         <td colspan="4"></td>
      </tr>
      <tr>
         <td colspan="2" style="{{ $th }}">TEPAT WAKTU</td>
         <td style="{{ $td }}">{{ $statistik['tepat_waktu'] }}</td>
         <td colspan="4"></td>
      </tr>
      <tr>
         <td colspan="2" style="{{ $th }}">TERLAMBAT</td>
         <td style="{{ $td }}">{{ $statistik['terlambat'] }}</td>
         <td colspan="4"></td>
      </tr>
      <tr>
         <td colspan="2" style="{{ $th }}">IZIN / SAKIT / CUTI</td>
         <td style="{{ $td }}">{{ $statistik['izin'] }}</td>
         <td colspan="4"></td>
      </tr>
      <tr>
         <td colspan="2" style="{{ $th }}">ALFA</td>
         <td style="{{ $td }}">{{ $statistik['alfa'] }}</td>
         <td colspan="4"></td>
      </tr>
   </tbody>
</table>

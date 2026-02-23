<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\Izin;
use App\Repositories\IzinRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IzinService extends BaseService
{
    protected FileUploadService $fileUploadService;

    public function __construct(
        IzinRepository $repository,
        FileUploadService $fileUploadService
    ) {
        parent::__construct($repository);
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get izin by pegawai
     */
    public function getByPegawai($pegawaiId)
    {
        return $this->repository->getByPegawai($pegawaiId);
    }

    /**
     * Get izin pending (untuk approval)
     */
    public function getPending()
    {
        return $this->repository->getPending();
    }

    /**
     * Get izin approved
     */
    public function getApproved()
    {
        return $this->repository->getApproved();
    }

    /**
     * Ajukan izin baru
     */
    public function ajukanIzin($pegawaiId, array $data)
    {
        // Validate tanggal
        $tglMulai = Carbon::parse($data['tgl_mulai']);
        $tglSelesai = Carbon::parse($data['tgl_selesai']);

        if ($tglSelesai->lt($tglMulai)) {
            throw new \Exception('Tanggal selesai tidak boleh lebih awal dari tanggal mulai.');
        }

        // Check overlap
        if ($this->repository->checkOverlap($pegawaiId, $data['tgl_mulai'], $data['tgl_selesai'])) {
            throw new \Exception('Izin yang Anda ajukan bertabrakan dengan izin lain yang sudah ada.');
        }

        // Check max hari (jika ada limit)
        $jenisIzin = \App\Models\JenisIzin::find($data['jenis_izin_id']);
        if ($jenisIzin) {
            $isCuti = str_contains(strtolower($jenisIzin->nama), 'cuti') || str_contains(strtolower($jenisIzin->kode), 'cuti');
            
            // Aturan khusus Cuti: Minimal 7 hari sebelumnya
            if ($isCuti) {
                $today = now()->startOfDay();
                if ($today->diffInDays($tglMulai, false) < 7) {
                    throw new \Exception("Pengajuan {$jenisIzin->nama} harus dilakukan minimal 7 hari (1 minggu) sebelumnya.");
                }
            }

            if ($jenisIzin->max_hari) {
                $jumlahHari = $tglMulai->diffInDays($tglSelesai) + 1;
                if ($jumlahHari > $jenisIzin->max_hari) {
                    throw new \Exception("Maksimal izin {$jenisIzin->nama} adalah {$jenisIzin->max_hari} hari.");
                }
            }
        }

        // Check apakah butuh surat
        if ($jenisIzin && $jenisIzin->butuh_surat && empty($data['file_surat'])) {
            throw new \Exception("Izin {$jenisIzin->nama} memerlukan surat pendukung.");
        }

        // Upload file surat jika ada
        $filePath = null;
        if (isset($data['file_surat']) && $data['file_surat']) {
            $media = $this->fileUploadService->upload($data['file_surat'], 'izin/surat', 'public');
            $filePath = $media->path;
        }

        return $this->create([
            'pegawai_id' => $pegawaiId,
            'jenis_izin_id' => $data['jenis_izin_id'],
            'tgl_mulai' => $data['tgl_mulai'],
            'tgl_selesai' => $data['tgl_selesai'],
            'alasan' => $data['alasan'],
            'file_surat' => $filePath,
            'status_approval' => Izin::STATUS_PENDING,
        ]);
    }

    /**
     * Approve izin
     */
    public function approveIzin($izinId, ?string $catatan = null)
    {
        return DB::transaction(function () use ($izinId, $catatan) {
            $izin = $this->find($izinId);

            if ($izin->status_approval !== Izin::STATUS_PENDING) {
                throw new \Exception('Izin ini sudah diproses sebelumnya.');
            }

            // Update status izin
            $izin->update([
                'status_approval' => Izin::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'catatan_admin' => $catatan,
            ]);

            // Generate record absensi untuk tanggal izin
            $this->generateAbsensiIzin($izin);

            return $izin->fresh();
        });
    }

    /**
     * Reject izin
     */
    public function rejectIzin($izinId, string $catatan)
    {
        $izin = $this->find($izinId);

        if ($izin->status_approval !== Izin::STATUS_PENDING) {
            throw new \Exception('Izin ini sudah diproses sebelumnya.');
        }

        $izin->update([
            'status_approval' => Izin::STATUS_REJECTED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'catatan_admin' => $catatan,
        ]);

        return $izin->fresh();
    }

    /**
     * Generate record absensi untuk tanggal izin yang di-approve
     */
    protected function generateAbsensiIzin(Izin $izin)
    {
        $jenisIzin = $izin->jenisIzin;
        
        $kodeStr = strtolower(($jenisIzin->kode ?? '') . ' ' . ($jenisIzin->nama ?? ''));
        
        $status = 'Izin';
        if (str_contains($kodeStr, 'sakit')) {
            $status = 'Sakit';
        } elseif (str_contains($kodeStr, 'cuti') || str_contains($kodeStr, 'melahirkan') || str_contains($kodeStr, 'menikah') || str_contains($kodeStr, 'duka')) {
            $status = 'Cuti';
        }

        $current = Carbon::parse($izin->tgl_mulai);
        $end = Carbon::parse($izin->tgl_selesai);

        $pegawai = $izin->pegawai;
        $shiftId = $pegawai->shift_id;

        while ($current->lte($end)) {
            Absensi::updateOrCreate(
                [
                    'pegawai_id' => $izin->pegawai_id,
                    'tanggal' => $current->toDateString(),
                ],
                [
                    'shift_id' => $shiftId,
                    'status' => $status,
                    'keterangan' => "Izin: {$jenisIzin->nama} - {$izin->alasan}",
                ]
            );

            $current->addDay();
        }
    }

    /**
     * Cancel izin (hanya untuk izin pending)
     */
    public function cancelIzin($izinId, $pegawaiId)
    {
        $izin = $this->find($izinId);

        if ($izin->pegawai_id !== $pegawaiId) {
            throw new \Exception('Anda tidak berhak membatalkan izin ini.');
        }

        if ($izin->status_approval !== Izin::STATUS_PENDING) {
            throw new \Exception('Hanya izin dengan status Pending yang bisa dibatalkan.');
        }

        return $this->delete($izinId);
    }

    public function getStatistik()
    {
        return $this->repository->getStatistik();
    }
}

<?php

namespace App\Services;

use App\Repositories\KunjunganRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class KunjunganService extends BaseService
{
    protected TelegramService $telegramService;

    /**
     * Chat ID khusus untuk notifikasi kunjungan
     */
    protected string $kunjunganChatId;

    public function __construct(
        KunjunganRepository $repository,
        TelegramService $telegramService
    ) {
        parent::__construct($repository);
        $this->telegramService = $telegramService;
        $this->kunjunganChatId = config('services.telegram.kunjungan_chat_id', '-5232586927');
    }

    /**
     * Simpan kunjungan baru dengan pengecekan jarak
     */
    public function createKunjungan(int $userId, array $data, ?UploadedFile $foto = null)
    {
        $data['user_id'] = $userId;

        // 1. Cek Jarak (Geofencing 50m)
        $kunjunganData = $this->validateDistance($data);
        
        // 2. Upload foto (Wajib karena di request sudah divalidasi)
        if ($foto) {
            $data['foto_kunjungan'] = $foto->store('kunjungan', 'public');
        }

        $kunjungan = $this->repository->create($data);

        // 3. Kirim notifikasi Telegram
        $this->sendTelegramNotification($kunjungan, $kunjunganData['distance']);

        return $kunjungan;
    }

    /**
     * Validasi jarak user ke lokasi mitra
     */
    protected function validateDistance(array $data)
    {
        $mitra = \App\Models\Mitra::findOrFail($data['mitra_id']);
        $distance = null;

        // Jika mitra punya koordinat, wajib cek jarak
        if ($mitra->latitude && $mitra->longitude) {
            $distance = $this->calculateDistance(
                $data['user_lat'],
                $data['user_lng'],
                $mitra->latitude,
                $mitra->longitude
            );

            // Toleransi 50 meter (0.05 km)
            if ($distance > 0.05) {
                $distInMeters = round($distance * 1000);
                throw new \Exception("Anda berada terlalu jauh dari outlet ({$distInMeters}m). Maksimal toleransi adalah 50m.");
            }
        }

        return [
            'distance' => $distance
        ];
    }

    /**
     * Get history kunjungan by user
     */
    public function getByUser($userId)
    {
        return $this->repository->getByUser($userId);
    }

    /**
     * Get semua kunjungan (admin) dengan filter
     */
    public function getAllFiltered($filters = [])
    {
        return $this->repository->getAllWithRelations($filters);
    }

    /**
     * Get detail kunjungan
     */
    public function findWithRelations($id)
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Admin hapus kunjungan
     */
    public function adminDelete($id)
    {
        $kunjungan = $this->repository->find($id);

        // Hapus foto jika ada
        if ($kunjungan->foto_kunjungan && Storage::disk('public')->exists($kunjungan->foto_kunjungan)) {
            Storage::disk('public')->delete($kunjungan->foto_kunjungan);
        }

        return $this->repository->delete($id);
    }

    /**
     * Haversine Formula untuk hitung jarak (km)
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return ($miles * 1.609344);
    }

    /**
     * Kirim notifikasi Telegram ke chat ID khusus kunjungan
     */
    protected function sendTelegramNotification($kunjungan, $distance = null)
    {
        $kunjungan->load(['user.pegawai', 'mitra']);

        $pegawaiName = $kunjungan->user->pegawai->nama_lengkap
            ?? $kunjungan->user->pegawai->nama
            ?? $kunjungan->user->name
            ?? '-';

        $photoPath = null;
        if ($kunjungan->foto_kunjungan) {
            $root = config('filesystems.disks.public.root');
            $photoPath = rtrim($root, '/') . '/' . ltrim($kunjungan->foto_kunjungan, '/');
        }

        // Build message
        $vType = $kunjungan->visit_type == 'routine' ? 'Kunjungan Rutin' : 'By Request';
        
        $message = "<b>📋 LAPORAN KUNJUNGAN QC ({$vType})</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "<b>📅 Tanggal:</b> " . $kunjungan->tanggal_kunjungan->format('d M Y') . "\n";
        $message .= "<b>👤 Petugas:</b> {$pegawaiName}\n";
        $message .= "<b>🏪 Outlet:</b> " . ($kunjungan->mitra->name ?? '-') . "\n";
        
        if ($distance !== null) {
            $message .= "<b>📍 Jarak dari Lokasi Mitra:</b> " . round($distance * 1000) . " meter\n";
        } else {
            $message .= "<b>📍 Jarak dari Lokasi Mitra:</b> (Titik outlet belum diatur)\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "<b>☕ Espresso Calibration:</b>\n<i>{$kunjungan->espresso_calibration}</i>\n\n";
        $message .= "<b>👅 Taste Notes:</b>\n<i>{$kunjungan->taste_notes}</i>\n\n";

        if ($kunjungan->flow_of_customers) {
            $message .= "<b>🌊 Flow of Customers:</b>\n{$kunjungan->flow_of_customers}\n\n";
        }

        if ($kunjungan->feedback) {
            $message .= "<b>💬 Feedback:</b>\n{$kunjungan->feedback}\n\n";
        }

        if ($kunjungan->problem) {
            $message .= "<b>⚠️ Problem:</b>\n<pre>{$kunjungan->problem}</pre>\n\n";
        }

        if ($kunjungan->note) {
            $message .= "<b>📝 Note:</b>\n{$kunjungan->note}\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━━━━";

        // Kirim ke chat ID khusus (hardcoded)
        if ($photoPath && file_exists($photoPath)) {
            $this->telegramService->sendPhoto($photoPath, $message, 'HTML', $this->kunjunganChatId);
        } else {
            $this->telegramService->sendMessage($message, 'HTML', $this->kunjunganChatId);
        }
    }
}


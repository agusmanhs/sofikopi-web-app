<?php

namespace App\Services;

use App\Models\Mitra;
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
        $mitra = Mitra::findOrFail($data['mitra_id']);
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
            'distance' => $distance,
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
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        return $miles * 1.609344;
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
            $photoPath = rtrim($root, '/').'/'.ltrim($kunjungan->foto_kunjungan, '/');
        }

        $vType = $kunjungan->visit_type == 'routine' ? 'Kunjungan Rutin' : 'By Request';

        $details = [
            'Tanggal' => $kunjungan->tanggal_kunjungan->format('d M Y'),
            'Petugas' => $pegawaiName,
            'Outlet' => $kunjungan->mitra->name ?? '-',
            'Jarak dari Lokasi Mitra' => $distance !== null
                ? round($distance * 1000).' meter'
                : '(Titik outlet belum diatur)',
            'Espresso Calibration' => "<i>{$kunjungan->espresso_calibration}</i>",
            'Taste Notes' => "<i>{$kunjungan->taste_notes}</i>",
        ];

        if ($kunjungan->flow_of_customers) {
            $details['Flow of Customers'] = $kunjungan->flow_of_customers;
        }

        if ($kunjungan->feedback) {
            $details['Feedback'] = $kunjungan->feedback;
        }

        if ($kunjungan->problem) {
            $details['Problem'] = "<pre>{$kunjungan->problem}</pre>";
        }

        if ($kunjungan->note) {
            $details['Note'] = $kunjungan->note;
        }

        // Queued dispatch (non-blocking) instead of calling sendPhoto()/sendMessage()
        // directly — keeps this request from blocking on the Telegram API.
        $this->telegramService->notify(
            "LAPORAN KUNJUNGAN QC ({$vType})",
            $details,
            '📋',
            $photoPath && file_exists($photoPath) ? $photoPath : null,
            $this->kunjunganChatId
        );
    }
}

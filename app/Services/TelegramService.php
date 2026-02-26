<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $token;
    protected string $chatId;
    protected bool $enabled;

    public function __construct()
    {
        $this->token = config('services.telegram.token', '');
        $this->chatId = config('services.telegram.chat_id', '');
        $this->enabled = !empty($this->token) && !empty($this->chatId);
    }

    /**
     * Send message to Telegram
     * 
     * @param string $message
     * @param string|null $parseMode HTML or MarkdownV2
     * @return bool
     */
    public function sendMessage(string $message, ?string $parseMode = 'HTML'): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $this->chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            if (!$response->successful()) {
                Log::error('Telegram API Error: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram Service Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Metode NOTIFIKASI UMUM (Gampang dipakai)
     * Gunakan ini untuk kirim pesan kustom tambahan nanti.
     * 
     * Cara Pakai di Controller/Service:
     * app(\App\Services\TelegramService::class)->notify("JUDUL PESAN", [
     *     "Nama" => "Budi",
     *     "Aksi" => "Melakukan Sesuatu"
     * ], "🚀");
     * 
     * @param string $title Judul Pesan (Bold)
     * @param array $details Array Key => Value untuk isi detail
     * @param string $icon Emoji untuk ikon depan judul
     */
    public function notify(string $title, array $details, string $icon = 'ℹ️'): void
    {
        $message = "<b>{$icon} {$title}</b>\n\n";
        foreach ($details as $label => $value) {
            $message .= "<b>{$label}:</b> {$value}\n";
        }
        $this->sendMessage($message);
    }

    /**
     * Formatting message for Clock In
     */
    public function notifyAbsenMasuk($absensi): void
    {
        $pegawai = $absensi->pegawai;
        $shift = $absensi->shift;
        $time = $absensi->jam_masuk;
        $status = $absensi->status;

        $this->notify("ABSEN MASUK", [
            'Nama' => ($pegawai->nama_lengkap ?? $pegawai->nama ?? '-'),
            'Divisi' => ($pegawai->divisi->nama ?? '-'),
            'Shift' => ($shift->nama ?? '-') . " (" . ($shift->jam_masuk->format('H:i') ?? '-') . ")",
            'Waktu Absen' => $time,
            'Status' => $status,
            'Lokasi' => $absensi->lokasi_masuk
        ], '✅');
    }

    /**
     * Formatting message for Clock Out
     */
    public function notifyAbsenPulang($absensi): void
    {
        $pegawai = $absensi->pegawai;
        $shift = $absensi->shift;
        $time = $absensi->jam_pulang;
        $keterangan = $absensi->keterangan ?? '-';

        $details = [
            'Nama' => ($pegawai->nama_lengkap ?? $pegawai->nama ?? '-'),
            'Divisi' => ($pegawai->divisi->nama ?? '-'),
            'Shift' => ($shift->nama ?? '-') . " (" . ($shift->jam_pulang->format('H:i') ?? '-') . ")",
            'Waktu Pulang' => $time,
            'Lokasi' => $absensi->lokasi_pulang
        ];

        if ($keterangan != '-') {
            $details['Keterangan'] = $keterangan;
        }

        $this->notify("ABSEN PULANG", $details, '🚩');
    }

    /**
     * Formatting message for Izin Created
     */
    public function notifyIzinCreated($izin): void
    {
        $pegawai = $izin->pegawai;
        $jenisIzin = $izin->jenisIzin;

        $this->notify("PENGAJUAN IZIN BARU", [
            'Nama' => ($pegawai->nama_lengkap ?? $pegawai->nama ?? '-'),
            'Jenis' => ($jenisIzin->nama ?? '-'),
            'Tanggal' => $izin->tgl_mulai->format('d/m/Y') . ($izin->tgl_mulai != $izin->tgl_selesai ? " s/d " . $izin->tgl_selesai->format('d/m/Y') : ""),
            'Alasan' => $izin->alasan,
            'Status' => 'Menunggu Persetujuan'
        ], '📝');
    }

    /**
     * Formatting message for Izin Approved/Rejected
     */
    public function notifyIzinStatus($izin): void
    {
        $pegawai = $izin->pegawai;
        $status = $izin->status_approval; // Assuming 'Approved' or 'Rejected'
        $icon = $status == 'Approved' ? '✅' : '❌';
        $title = "PENGEMBALIAN IZIN (" . strtoupper($status) . ")";

        $this->notify($title, [
            'Nama' => ($pegawai->nama_lengkap ?? $pegawai->nama ?? '-'),
            'Jenis' => ($izin->jenisIzin->nama ?? '-'),
            'Tanggal' => $izin->tgl_mulai->format('d/m/Y') . ($izin->tgl_mulai != $izin->tgl_selesai ? " s/d " . $izin->tgl_selesai->format('d/m/Y') : ""),
            'Status' => $status,
            'Catatan Admin' => $izin->catatan_admin ?? '-'
        ], $icon);
    }
}

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
    public function sendMessage(string $message, ?string $parseMode = 'HTML', ?string $chatId = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $targetChatId = $chatId ?? $this->chatId;

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $targetChatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            if (!$response->successful()) {
                Log::error('Telegram API Error (sendMessage): ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram Service Exception (sendMessage): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send photo to Telegram
     * 
     * @param string $photoPath Absolute path or URL
     * @param string $caption
     * @param string|null $parseMode HTML or MarkdownV2
     * @param string|null $chatId
     * @return bool
     */
    public function sendPhoto(string $photoPath, string $caption = '', ?string $parseMode = 'HTML', ?string $chatId = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $targetChatId = $chatId ?? $this->chatId;

        try {
            // Build the request
            $pendingRequest = Http::asMultipart();
            
            // If it's a local file path and exists
            if (!empty($photoPath) && file_exists($photoPath)) {
                $fileContents = @file_get_contents($photoPath);
                if ($fileContents === false) {
                     Log::warning("Telegram Service: Failed to read photo file at {$photoPath}");
                     return false;
                }
                $pendingRequest->attach('photo', $fileContents, basename($photoPath));
            } else {
                // If it's a URL or path doesn't exist, try sending as string (Telegram handles URLs)
                // BUT only if it looks like a URL
                if (filter_var($photoPath, FILTER_VALIDATE_URL)) {
                    return $this->sendMessageWithPhotoUrl($photoPath, $caption, $parseMode, $targetChatId);
                }
                Log::warning("Telegram Service: Photo path is neither a valid file nor a URL: {$photoPath}");
                return false;
            }

            $response = $pendingRequest->post("https://api.telegram.org/bot{$this->token}/sendPhoto", [
                'chat_id' => $targetChatId,
                'caption' => $caption,
                'parse_mode' => $parseMode,
            ]);

            if (!$response->successful()) {
                Log::error('Telegram API Error (sendPhoto): ' . $response->status() . ' - ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram Service Exception (sendPhoto): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Internal helper to send photo by URL
     */
    protected function sendMessageWithPhotoUrl(string $url, string $caption, ?string $parseMode = 'HTML', ?string $chatId = null): bool
    {
        $targetChatId = $chatId ?? $this->chatId;
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendPhoto", [
                'chat_id' => $targetChatId,
                'photo' => $url,
                'caption' => $caption,
                'parse_mode' => $parseMode,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            $this->safeLog('error', 'Telegram Service Exception (sendPhotoUrl): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message with Reply Keyboard
     */
    public function sendMessageWithKeyboard(string $message, array $keyboard, ?string $parseMode = 'HTML', ?string $chatId = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $targetChatId = $chatId ?? $this->chatId;

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $targetChatId,
                'text' => $message,
                'parse_mode' => $parseMode,
                'reply_markup' => [
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false
                ]
            ]);

            if (!$response->successful()) {
                $this->safeLog('error', 'Telegram API Error (keyboard): ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->safeLog('error', 'Telegram Service Exception (keyboard): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message with Inline Keyboard
     */
    public function sendMessageWithInlineKeyboard(string $message, array $inlineKeyboard, ?string $parseMode = 'HTML', ?string $chatId = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $targetChatId = $chatId ?? $this->chatId;

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->token}/sendMessage", [
                'chat_id' => $targetChatId,
                'text' => $message,
                'parse_mode' => $parseMode,
                'reply_markup' => [
                    'inline_keyboard' => $inlineKeyboard
                ]
            ]);

            if (!$response->successful()) {
                $this->safeLog('error', 'Telegram API Error (inline_keyboard): ' . $response->body());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->safeLog('error', 'Telegram Service Exception (inline_keyboard): ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Safe logging to prevent application crash if log file is not writable
     */
    protected function safeLog(string $level, string $message): void
    {
        try {
            Log::log($level, $message);
        } catch (\Exception $e) {
            // Silently fail if logging is not possible
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
     * @param string|null $photoPath Path foto (opsional)
     */
    public function notify(string $title, array $details, string $icon = 'ℹ️', ?string $photoPath = null): void
    {
        $message = "<b>{$icon} {$title}</b>\n\n";
        foreach ($details as $label => $value) {
            $message .= "<b>{$label}:</b> {$value}\n";
        }

        $sent = false;
        try {
            if ($photoPath) {
                $sent = $this->sendPhoto($photoPath, $message);
                if (!$sent) {
                    $this->safeLog('info', "Telegram Service: Photo notification failed, falling back to text.");
                }
            }
        } catch (\Exception $e) {
            $this->safeLog('error', "Telegram Service: Error in photo part: " . $e->getMessage());
        }

        if (!$sent) {
            $this->sendMessage($message);
        }
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

        $photoPath = null;
        if ($absensi->foto_masuk) {
            $root = config('filesystems.disks.public.root');
            $photoPath = rtrim($root, '/') . '/' . ltrim($absensi->foto_masuk, '/');
        }

        $this->notify("ABSEN MASUK", [
            'Nama' => ($pegawai->nama_lengkap ?? $pegawai->nama ?? '-'),
            'Divisi' => ($pegawai->divisi->nama ?? '-'),
            'Shift' => ($shift->nama ?? '-') . " (" . ($shift->jam_masuk->format('H:i') ?? '-') . ")",
            'Waktu Absen' => $time,
            'Status' => $status,
            'Lokasi' => $absensi->lokasi_masuk
        ], '✅', $photoPath);
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

        $photoPath = null;
        if ($absensi->foto_pulang) {
            $root = config('filesystems.disks.public.root');
            $photoPath = rtrim($root, '/') . '/' . ltrim($absensi->foto_pulang, '/');
        }

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

        $this->notify("ABSEN PULANG", $details, '🚩', $photoPath);
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

<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BackupService
{
    protected TelegramService $telegramService;

    protected string $backupChatId;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
        $this->backupChatId = (string) config('services.telegram.backup_chat_id', '');
    }

    /**
     * Export database langsung sebagai file SQL (seperti phpMyAdmin)
     * Return path ke file temporary untuk di-download
     */
    public function exportDatabase(): array
    {
        $dbName = Config::get('database.connections.mysql.database');
        $dbUser = Config::get('database.connections.mysql.username');
        $dbPass = Config::get('database.connections.mysql.password');
        $dbHost = Config::get('database.connections.mysql.host');
        $dbPort = Config::get('database.connections.mysql.port', '3306');

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $fileName = "sofikopi_backup_{$timestamp}.sql";
        $tempPath = storage_path("app/temp/{$fileName}");

        // Pastikan folder temp ada
        if (! is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        try {
            $command = sprintf(
                'mysqldump --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($dbHost),
                escapeshellarg($dbPort),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($tempPath)
            );

            $result = Process::run($command);

            if ($result->failed()) {
                throw new \Exception($result->errorOutput());
            }

            // Cek apakah file berhasil dibuat dan tidak kosong
            if (! file_exists($tempPath) || filesize($tempPath) === 0) {
                throw new \Exception('File SQL yang dihasilkan kosong atau tidak ditemukan.');
            }

            $fileSize = $this->formatBytes(filesize($tempPath));

            // Kirim notifikasi Telegram SUKSES
            $this->notifyTelegram(true, $fileName, $fileSize);

            return [
                'success' => true,
                'file_path' => $tempPath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
            ];
        } catch (\Exception $e) {
            Log::error('Database export gagal: '.$e->getMessage());

            // Kirim notifikasi Telegram GAGAL
            $this->notifyTelegram(false, $fileName, null, $e->getMessage());

            // Bersihkan file partial jika ada
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return [
                'success' => false,
                'message' => 'Gagal export database: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Kirim notifikasi ke Telegram
     */
    protected function notifyTelegram(bool $success, string $fileName, ?string $fileSize = null, ?string $error = null): void
    {
        try {
            if ($success) {
                // Queued dispatch (non-blocking) instead of calling sendMessage() directly.
                $this->telegramService->notify('DATABASE EXPORT SUKSES', [
                    'File' => $fileName,
                    'Ukuran' => $fileSize,
                    'Metode' => 'Manual Export (Admin)',
                    'Waktu' => now()->format('d M Y, H:i:s'),
                    'Catatan' => 'File langsung diunduh ke perangkat admin.',
                ], '✅', null, $this->backupChatId);
            } else {
                $shortError = mb_substr($error ?? 'Unknown error', 0, 500);
                $this->telegramService->notify('DATABASE EXPORT GAGAL!', [
                    'Penyebab' => "<code>{$shortError}</code>",
                    'Waktu' => now()->format('d M Y, H:i:s'),
                    'Catatan' => 'Mohon cek server segera.',
                ], '❌', null, $this->backupChatId);
            }
        } catch (\Exception $e) {
            Log::warning('Telegram notification gagal: '.$e->getMessage());
        }
    }

    /**
     * Helper to format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }
}

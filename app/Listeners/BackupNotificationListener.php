<?php

namespace App\Listeners;

use Spatie\Backup\Events\BackupWasSuccessful;
use Spatie\Backup\Events\BackupHasFailed;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class BackupNotificationListener
{
    /**
     * Chat ID Khusus untuk Backup
     */
    protected $backupChatId = '-5232586927';

    public function __construct(
        protected TelegramService $telegramService
    ) {}

    /**
     * Handle backup successful event
     */
    public function handleBackupSuccess(BackupWasSuccessful $event)
    {
        $backupName = config('app.name', 'Sofikopi');
        $disks = implode(', ', config('backup.backup.destination.disks'));
        
        $message = "<b>✅ DATABASE BACKUP SUKSES</b>\n\n";
        $message .= "<b>Project:</b> {$backupName}\n";
        $message .= "<b>Destinasi:</b> Google Drive\n";
        $message .= "<b>Waktu:</b> " . now()->format('d M Y, H:i:s') . "\n\n";
        $message .= "🔥 <i>Data berhasil dicadangkan dengan aman ke cloud.</i>";

        $this->telegramService->sendMessage($message, 'HTML', $this->backupChatId);
    }

    /**
     * Handle backup failed event
     */
    public function handleBackupFailed(BackupHasFailed $event)
    {
        $error = $event->exception->getMessage();
        
        // Simplifikasi pesan error agar tidak terlalu teknis
        if (str_contains($error, 'mysqldump')) {
            $error = "Perintah 'mysqldump' tidak ditemukan di server. Pastikan mysql-client sudah terinstall.";
        } elseif (str_contains($error, 'SSL')) {
            $error = "Masalah sertifikat SSL/koneksi aman database.";
        } elseif (str_contains($error, 'UnableToReadFile')) {
            $error = "Google Drive tidak bisa memvalidasi folder tujuan. Cek apakah Folder ID di .env sudah benar dan bisa diakses.";
        }

        // Potong pesan jika terlalu panjang
        $shortError = mb_substr($error, 0, 800) . (mb_strlen($error) > 800 ? '...' : '');

        $message = "<b>❌ DATABASE BACKUP GAGAL!</b>\n\n";
        $message .= "<b>Penyebab:</b> <code>{$shortError}</code>\n";
        $message .= "<b>Waktu:</b> " . now()->format('d M Y, H:i:s') . "\n\n";
        $message .= "🚨 <i>Mohon cek server segera untuk memastikan data tetap aman.</i>";

        $this->telegramService->sendMessage($message, 'HTML', $this->backupChatId);
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe($events)
    {
        $events->listen(
            BackupWasSuccessful::class,
            [BackupNotificationListener::class, 'handleBackupSuccess']
        );

        $events->listen(
            BackupHasFailed::class,
            [BackupNotificationListener::class, 'handleBackupFailed']
        );
    }
}

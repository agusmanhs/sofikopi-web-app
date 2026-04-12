<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BackupService
{
    /**
     * Get list of all backup files
     */
    public function getBackups()
    {
        $backupName = config('backup.backup.name');
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        
        $backups = [];
        
        try {
            // Get all files in the backup directory
            $files = $disk->allFiles($backupName);

            foreach ($files as $file) {
                if (str_ends_with($file, '.zip')) {
                    // Jika backupName kosong, ambil nama file apa adanya
                    $fileName = $backupName ? str_replace($backupName . '/', '', $file) : basename($file);
                    
                    $backups[] = [
                        'file_name' => $fileName,
                        'file_size' => $this->formatBytes($disk->size($file)),
                        'last_modified' => Carbon::createFromTimestamp($disk->lastModified($file))->format('d M Y, H:i:s'),
                        'file_path' => $file,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Jika folder belum ada (baru pertama kali), abaikan saja dan return array kosong
            Log::info("BackupService: Folder {$backupName} belum tersedia atau belum ada file.");
        }

        // Return latest first
        return array_reverse($backups);
    }

    /**
     * Run backup command
     * 
     * @param string $option empty, '--only-db', '--only-files'
     */
    public function runBackup($option = '')
    {
        try {
            // Kita gunakan call agar termonitor di log
            Artisan::call('backup:run ' . $option);
            return [
                'success' => true,
                'message' => 'Backup berhasil dijalankan di background.'
            ];
        } catch (\Exception $e) {
            Log::error("Backup gagal: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal menjalankan backup: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup($filePath)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        
        if ($disk->exists($filePath)) {
            return $disk->delete($filePath);
        }
        return false;
    }

    /**
     * Download a backup file
     */
    public function downloadBackup($filePath)
    {
        $diskName = config('backup.backup.destination.disks')[0] ?? 'local';
        $disk = Storage::disk($diskName);
        
        if ($disk->exists($filePath)) {
            return $disk->download($filePath);
        }
        return null;
    }

    /**
     * Helper to format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

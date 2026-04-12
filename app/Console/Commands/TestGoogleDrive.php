<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestGoogleDrive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:test-gdrive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Drive connectivity for backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Sedang mencoba koneksi ke Google Drive...");

        try {
            $disk = Storage::disk('google');
            
            // 1. Cek List File
            $this->comment("1. Mencoba membaca daftar file di root...");
            $files = $disk->files();
            $this->info("Sukses! Ditemukan " . count($files) . " file.");

            // 2. Cek Tulis File
            $this->comment("2. Mencoba menulis file uji coba (test.txt)...");
            $content = "Test koneksi pada " . now()->toDateTimeString();
            $disk->put('test-koneksi-sofikopi.txt', $content);
            $this->info("Sukses! File berhasil diunggah.");

            // 3. Cek Hapus File
            $this->comment("3. Mencoba menghapus kembali file uji coba...");
            $disk->delete('test-koneksi-sofikopi.txt');
            $this->info("Sukses! File berhasil dihapus.");

            $this->newLine();
            $this->info("====================================");
            $this->info("✅ GOOGLE DRIVE ANDA SUDAH VALID!");
            $this->info("====================================");

        } catch (\Exception $e) {
            $this->error("====================================");
            $this->error("❌ KONEKSI GAGAL!");
            $this->error("Pesan Error: " . $e->getMessage());
            $this->error("====================================");
            $this->line("Detail Error:");
            $this->line($e->getTraceAsString());
        }
    }
}

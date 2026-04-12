# Panduan Deployment & Update VPS - Sofikopi Absensi

Dokumen ini berisi panduan teknis untuk menjaga kestabilan aplikasi saat dideploy ke VPS, terutama menangani sinkronisasi versi PHP antara Lokal (8.4) dan VPS (8.2).

## 1. Sinkronisasi Versi PHP (Penting!)

Untuk mencegah library terupdate ke versi yang hanya mendukung PHP 8.4 saat Anda menjalankan `composer update` di lokal, pastikan bagian `platform` di `composer.json` sudah terisi seperti berikut:

```json
"config": {
    "platform": {
        "php": "8.2.0"
    }
}
```

_Catatan: Ini sudah saya tambahkan di `composer.json` proyek Anda._

### Aturan Update Library:

1.  **Jalankan di Lokal:** Gunakan `composer update` seperti biasa. Berkat pengaturan `platform`, Composer akan mendownload paket yang **pasti bisa** jalan di PHP 8.2 (VPS).
2.  **Upload `composer.lock`:** Selalu sertakan file `composer.lock` saat upload/push ke VPS.
3.  **Jalankan di Server:** Di VPS, cukup jalankan:
    ```bash
    composer install --no-dev --optimize-autoloader
    ```
    **JANGAN** jalankan `composer update` di VPS karena bisa memakan banyak RAM dan berisiko merusak dependensi yang sudah stabil di `lock` file.

---

## 2. Cara Aktivasi Fitur Backup di VPS

Setelah mengunggah kode terbaru ke VPS, lakukan langkah-langkah berikut:

### A. Registrasi Menu Admin

Jalankan seeder untuk memunculkan menu "Database Backup" di panel admin:

```bash
php artisan db:seed --class=BackupMenuSeeder
```

### B. Konfigurasi Scheduler (Cron Job)

Agar backup harian berjalan otomatis (jam 02:00 pagi sesuai `routes/console.php`), Anda harus memicu Scheduler Laravel di sistem VPS.

Buka crontab VPS:

```bash
crontab -e
```

Lalu tambahkan baris berikut di paling bawah (asumsi path proyek):

```bash
* * * * * cd /path/ke/proyek/anda && php artisan schedule:run >> /dev/null 2>&1
```

### C. Persyaratan Library Server

Pastikan VPS memiliki package pendukung untuk proses backup:

```bash
# Untuk Ubuntu/Debian
sudo apt update
sudo apt install mysql-client zip
```

---

## 4. Konfigurasi Google Drive Cloud Backup

Untuk mengaktifkan backup ke Google Drive, Anda perlu mengisi variabel berikut di file `.env` server:

```env
GOOGLE_DRIVE_CLIENT_ID="xxx"
GOOGLE_DRIVE_CLIENT_SECRET="xxx"
GOOGLE_DRIVE_REFRESH_TOKEN="xxx"
GOOGLE_DRIVE_FOLDER_ID="xxx"
```

### Cara Mendapatkan Credentials:
1.  Buka [Google Cloud Console](https://console.cloud.google.com/).
2.  Buat Proyek baru, aktifkan **Google Drive API**.
3.  Di menu **Credentials**, buat **OAuth Client ID** (pilih Web Application).
4.  Tambahkan `https://developers.google.com/oauthplayground` ke **Authorized redirect URIs**.
5.  Gunakan [OAuth2 Playground](https://developers.google.com/oauthplayground) untuk mendapatkan **Refresh Token**:
    *   Klik ikon gerigi (Settings), centang "Use your own OAuth credentials", masukkan Client ID & Secret.
    *   Cari "Drive API v3" di daftar sebelah kiri, pilih scope `https://www.googleapis.com/auth/drive`.
    *   Klik "Authorize APIs", login, lalu klik "Exchange authorization code for tokens".
    *   Salin **Refresh Token** yang muncul.
6.  **Folder ID:** Buka folder di Google Drive Anda, ID-nya adalah deretan karakter di akhir URL browser Anda.

---

## 3. Checklist Sebelum Push ke VPS

1.  [ ] Jalankan `php artisan config:cache` dan `php artisan route:cache` untuk performa.
2.  [ ] Cek folder `storage/app/backup-temp` pastikan writable.
3.  [ ] Cek file `.env` di VPS, pastikan `APP_ENV=production` dan `DB_DATABASE` sudah sesuai.

---

_Dibuat oleh: Antigravity AI Coding Assistant_

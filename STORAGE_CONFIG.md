# Konfigurasi Storage Path untuk VPS

## Masalah yang Ditemukan

Kode sebelumnya menggunakan **hardcoded path** `storage_path('app/public')` yang akan selalu mengarah ke:

```
/var/www/sofikopi_absensi/storage/app/public/
```

Padahal di VPS Anda, struktur storage menggunakan folder terpisah:

```
/var/www/sofikopi_absensi/storage_sistem/app/public/
```

## Solusi yang Diterapkan

### 1. Update `config/filesystems.php`

**Sebelum:**

```php
'public' => [
    'root' => storage_path('app/public'),
],
```

**Sesudah:**

```php
'public' => [
    'root' => env('STORAGE_PATH') ? env('STORAGE_PATH') . '/app/public' : storage_path('app/public'),
],
```

Sekarang path storage bisa dikonfigurasi via environment variable `STORAGE_PATH`.

### 2. Update Symlink Configuration

**Sebelum:**

```php
'links' => [
    public_path('storage') => storage_path('app/public'),
],
```

**Sesudah:**

```php
'links' => [
    public_path('storage') => env('STORAGE_PATH') ? env('STORAGE_PATH') . '/app/public' : storage_path('app/public'),
],
```

## Cara Penggunaan

### Development (Local)

**File:** `.env`

```bash
# Tidak perlu set STORAGE_PATH
# Akan otomatis menggunakan storage/app/public
```

**Lokasi file upload:**

```
/home/ooka/BACKUP ARCH/jinx/Kerja/web-absensi/base-laravel/storage/app/public/absensi/
```

---

### Production (VPS)

**File:** `.env` di VPS

```bash
# Tambahkan baris ini:
STORAGE_PATH=/var/www/sofikopi_absensi/storage_sistem
```

**Lokasi file upload:**

```
/var/www/sofikopi_absensi/storage_sistem/app/public/absensi/
```

## Langkah Deploy ke VPS

### 1. Update `.env` di VPS

```bash
cd /var/www/sofikopi_absensi
nano .env
```

Tambahkan:

```bash
STORAGE_PATH=/var/www/sofikopi_absensi/storage_sistem
```

### 2. Buat Folder Storage_Sistem (jika belum ada)

```bash
mkdir -p /var/www/sofikopi_absensi/storage_sistem/app/public
mkdir -p /var/www/sofikopi_absensi/storage_sistem/app/public/absensi/masuk
mkdir -p /var/www/sofikopi_absensi/storage_sistem/app/public/absensi/pulang
```

### 3. Set Permission

```bash
sudo chown -R www-data:www-data /var/www/sofikopi_absensi/storage_sistem
sudo chmod -R 775 /var/www/sofikopi_absensi/storage_sistem/app/public
```

### 4. Recreate Symlink

```bash
cd /var/www/sofikopi_absensi
rm -f public/storage
php artisan storage:link
```

Output yang benar:

```
The [public/storage] link has been connected to [/var/www/sofikopi_absensi/storage_sistem/app/public].
```

### 5. Clear Cache

```bash
php artisan config:clear
php artisan cache:clear
```

### 6. Test Upload

Coba absen masuk/pulang, lalu cek:

```bash
ls -la /var/www/sofikopi_absensi/storage_sistem/app/public/absensi/masuk/
```

Seharusnya ada file foto baru.

## Verifikasi

### Cek Symlink

```bash
ls -la /var/www/sofikopi_absensi/public/storage
```

Output:

```
lrwxrwxrwx 1 www-data www-data 58 Jan 30 16:00 storage -> /var/www/sofikopi_absensi/storage_sistem/app/public
```

### Cek Config

```bash
php artisan tinker
```

```php
config('filesystems.disks.public.root')
// Output: /var/www/sofikopi_absensi/storage_sistem/app/public
```

### Test URL

```bash
curl -I https://yourdomain.com/storage/absensi/masuk/test.jpg
```

## Keuntungan Solusi Ini

✅ **Backward Compatible**: Tetap bisa jalan di development tanpa perubahan  
✅ **Flexible**: Bisa custom storage path per environment  
✅ **Clean**: Tidak perlu hardcode path di kode  
✅ **Scalable**: Mudah pindah ke cloud storage (S3) nanti

## Troubleshooting

### Problem: Foto masih tersimpan di `storage/` bukan `storage_sistem/`

**Solusi:**

```bash
# Pastikan .env sudah benar
grep STORAGE_PATH /var/www/sofikopi_absensi/.env

# Clear config cache
php artisan config:clear

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Problem: Permission denied saat upload

**Solusi:**

```bash
sudo chown -R www-data:www-data /var/www/sofikopi_absensi/storage_sistem
sudo chmod -R 775 /var/www/sofikopi_absensi/storage_sistem
```

### Problem: 404 saat akses gambar

**Solusi:**

```bash
# Recreate symlink
rm -f public/storage
php artisan storage:link

# Cek nginx/apache config
# Pastikan allow symlink
```

## Catatan

-   **JANGAN** commit file `.env` ke git
-   **SELALU** backup folder `storage_sistem/app/public/absensi/` secara berkala
-   Pertimbangkan migrasi ke cloud storage untuk production yang lebih besar

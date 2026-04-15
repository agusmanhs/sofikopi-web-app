<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\KantorController;
use App\Http\Controllers\JenisIzinController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\IzinController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductsController;
use App\Services\TelegramService;

// Tes telegram
// Route::get('test-telegram', function () {
//     $telegramService = app(\App\Services\TelegramService::class);
//     $success = $telegramService->sendMessage('Kiw Kiw - Tes Koneksi');
    
//     if ($success) {
//         return "Berhasil mengirim pesan ke Telegram!";
//     } else {
//         return "Gagal mengirim pesan. Silakan cek file log (storage/logs/laravel.log jika ada) atau pastikan Chat ID dan Token benar.";
//     }
// })->name('test-telegram');
// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
});
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth', 'check.pegawai.status'])->group(function () {
    // Dashboard as home page
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // User CRUD routes
    Route::resource('user', UserController::class)->middleware('check.permission:user.index');

    // Role & Menu Management
    Route::resource('role', \App\Http\Controllers\RoleController::class)->middleware('check.permission:role.index');
    Route::resource('menu', \App\Http\Controllers\MenuController::class)->middleware('check.permission:menu.index');
    Route::get('permission', [\App\Http\Controllers\PermissionController::class, 'index'])->name('permission.index')->middleware('check.permission:permission.index');
    Route::put('permission', [\App\Http\Controllers\PermissionController::class, 'update'])->name('permission.update')->middleware('check.permission:permission.index');



    // Activity Log
    Route::get('activity-log', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('activity-log/data', [\App\Http\Controllers\ActivityLogController::class, 'getData'])->name('activity-log.data');
    Route::get('activity-log/statistics', [\App\Http\Controllers\ActivityLogController::class, 'statistics'])->name('activity-log.statistics');

    // ============== DATA MASTER ==============
    // Divisi
    Route::resource('divisi', DivisiController::class)->middleware('check.permission:divisi.index');

    // Shift
    Route::resource('shift', ShiftController::class)->middleware('check.permission:shift.index');
    Route::get('api/shifts/by-divisi/{divisi}', [ShiftController::class, 'getByDivisi'])->name('shift.by-divisi');

    // Kantor
    Route::resource('kantor', KantorController::class)->middleware('check.permission:kantor.index');

    // Jenis Izin
    Route::resource('jenis-izin', JenisIzinController::class)->middleware('check.permission:jenis-izin.index');

    // Pegawai
    Route::resource('pegawai', PegawaiController::class)->middleware('check.permission:pegawai.index');

    // Hari Libur
    Route::post('hari-libur/sync', [\App\Http\Controllers\HariLiburController::class, 'sync'])->name('hari-libur.sync')->middleware('check.permission:hari-libur.index');
    Route::resource('hari-libur', \App\Http\Controllers\HariLiburController::class)->middleware('check.permission:hari-libur.index');
    Route::get('api/hari-libur/events', [\App\Http\Controllers\HariLiburController::class, 'getEvents'])->name('api.hari-libur.events');

    // Product & Mitra Module (DATA MASTER)
    Route::prefix('master')->group(function () {
        // Product Management
        Route::resource('product-category', \App\Http\Controllers\ProductCategoryController::class);
        Route::resource('product-sub-category', \App\Http\Controllers\ProductSubCategoryController::class);
        Route::resource('products', \App\Http\Controllers\ProductsController::class);
        Route::get('api/product-sub-categories/by-category/{categoryId}', [\App\Http\Controllers\ProductSubCategoryController::class, 'getByCategory']);

        // Mitra Management
        Route::get('mitra/template', [\App\Http\Controllers\MitraController::class, 'downloadTemplate'])->name('mitra.template');
        Route::post('mitra/import', [\App\Http\Controllers\MitraController::class, 'import'])->name('mitra.import');
        Route::resource('mitra-category', \App\Http\Controllers\MitraCategoryController::class);
        Route::resource('mitra', \App\Http\Controllers\MitraController::class);

        // Wilayah (Regional Data)
        Route::get('wilayah/provinces', [\App\Http\Controllers\WilayahController::class, 'provinces'])->name('wilayah.provinces');
        Route::get('wilayah/regencies/{provinceCode}', [\App\Http\Controllers\WilayahController::class, 'regencies'])->name('wilayah.regencies');
        Route::get('wilayah/districts/{regencyCode}', [\App\Http\Controllers\WilayahController::class, 'districts'])->name('wilayah.districts');
        Route::post('wilayah/sync-provinces', [\App\Http\Controllers\WilayahController::class, 'syncProvinces'])->name('wilayah.sync-provinces');
        Route::post('wilayah/sync-regencies', [\App\Http\Controllers\WilayahController::class, 'syncRegencies'])->name('wilayah.sync-regencies');
        Route::post('wilayah/sync-districts', [\App\Http\Controllers\WilayahController::class, 'syncDistricts'])->name('wilayah.sync-districts');
    });

    // ============== ABSENSI ==============
    Route::prefix('absensi')->name('absensi.')->group(function () {
        // Halaman absensi untuk pegawai
        Route::get('/', [AbsensiController::class, 'index'])->name('index');
        Route::post('/masuk', [AbsensiController::class, 'absenMasuk'])->name('masuk');
        Route::post('/pulang', [AbsensiController::class, 'absenPulang'])->name('pulang');
        Route::post('/validate-location', [AbsensiController::class, 'validateLocation'])->name('validate-location');
        Route::get('/history', [AbsensiController::class, 'history'])->name('history');
        Route::get('/calendar', [AbsensiController::class, 'calendar'])->name('calendar');
        Route::get('/calendar-events', [AbsensiController::class, 'getCalendarEvents'])->name('calendar-events');

        // Dashboard & Rekap untuk Admin
        Route::get('/dashboard', [AbsensiController::class, 'dashboard'])->name('dashboard')->middleware('check.permission:absensi.dashboard');
        Route::get('/rekap', [AbsensiController::class, 'rekap'])->name('rekap')->middleware('check.permission:absensi.rekap');
        Route::get('/rekap/export', [AbsensiController::class, 'exportExcel'])->name('rekap.export')->middleware('check.permission:absensi.rekap');
        Route::get('/history/{pegawai_id}', [AbsensiController::class, 'showPegawaiHistory'])->name('pegawai-history')->middleware('check.permission:absensi.rekap');
    });

    // ============== IZIN ==============
    Route::prefix('izin')->name('izin.')->group(function () {
        // Route untuk admin (Harus di atas agar tidak tertangkap oleh /{izin})
        Route::prefix('admin')->name('admin.')->middleware('check.permission:izin.admin')->group(function () {
            Route::get('/', [IzinController::class, 'adminIndex'])->name('index');
            Route::get('/pending', [IzinController::class, 'pending'])->name('pending');
            Route::post('/{izin}/approve', [IzinController::class, 'approve'])->name('approve');
            Route::post('/{izin}/reject', [IzinController::class, 'reject'])->name('reject');
            Route::delete('/{izin}/cancel', [IzinController::class, 'adminCancel'])->name('cancel');
        });

        // Route untuk pegawai
        Route::get('/', [IzinController::class, 'index'])->name('index');
        Route::get('/create', [IzinController::class, 'create'])->name('create');
        Route::post('/', [IzinController::class, 'store'])->name('store');
        Route::get('/{izin}', [IzinController::class, 'show'])->name('show');
        Route::delete('/{izin}/cancel', [IzinController::class, 'cancel'])->name('cancel');
    });

    // ============== PROFILE ==============
    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::get('profile/password', [\App\Http\Controllers\ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // ============== INFORMASI ==============
    Route::resource('informasi', \App\Http\Controllers\InformasiController::class)
        ->middleware('check.permission:informasi');

    // ============== BACKUP ==============
    Route::prefix('backup')->name('backup.')->middleware('check.permission:backup')->group(function () {
        Route::get('/', [\App\Http\Controllers\BackupController::class, 'index'])->name('index');
        Route::post('/run', [\App\Http\Controllers\BackupController::class, 'store'])->name('run');
        Route::get('/download', [\App\Http\Controllers\BackupController::class, 'download'])->name('download');
        Route::delete('/delete', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('delete');
    });

    
});


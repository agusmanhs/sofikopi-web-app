<?php

use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\DivisiController;
use App\Http\Controllers\HariLiburController;
use App\Http\Controllers\InformasiController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\IzinController;
use App\Http\Controllers\JadwalKerjaController;
use App\Http\Controllers\JenisIzinController;
use App\Http\Controllers\KantorController;
use App\Http\Controllers\KunjunganController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MitraCategoryController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\PengaturanAbsensiController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ProductSubCategoryController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesDashboardController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesOrderManageController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;

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
    Route::resource('role', RoleController::class)->middleware('check.permission:role.index');
    Route::resource('menu', MenuController::class)->middleware('check.permission:menu.index');
    Route::get('permission', [PermissionController::class, 'index'])->name('permission.index')->middleware('check.permission:permission.index');
    Route::put('permission', [PermissionController::class, 'update'])->name('permission.update')->middleware('check.permission:permission.index');

    // Activity Log
    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('activity-log/data', [ActivityLogController::class, 'getData'])->name('activity-log.data');
    Route::get('activity-log/statistics', [ActivityLogController::class, 'statistics'])->name('activity-log.statistics');

    // ============== DATA MASTER ==============
    // Divisi
    Route::resource('divisi', DivisiController::class)->middleware('check.permission:divisi.index');

    // Shift
    Route::resource('shift', ShiftController::class)->middleware('check.permission:shift.index');
    Route::get('api/shifts/by-divisi/{divisi}', [ShiftController::class, 'getByDivisi'])->name('shift.by-divisi');

    // Jadwal Kerja (Penjadwalan Absen)
    Route::prefix('jadwal-kerja')->name('jadwal-kerja.')->middleware('check.permission:jadwal-kerja.index')->group(function () {
        Route::get('/', [JadwalKerjaController::class, 'index'])->name('index');
        Route::post('/pattern/{pegawai}', [JadwalKerjaController::class, 'savePattern'])->name('pattern');
        Route::post('/override', [JadwalKerjaController::class, 'storeOverride'])->name('override.store');
        Route::delete('/override/{id}', [JadwalKerjaController::class, 'destroyOverride'])->name('override.destroy');
    });

    // Pengaturan Absensi (batas jam absen & pengajuan izin, berlaku global)
    Route::prefix('pengaturan-absensi')->name('pengaturan-absensi.')->middleware('check.permission:pengaturan-absensi.index')->group(function () {
        Route::get('/', [PengaturanAbsensiController::class, 'index'])->name('index');
        Route::put('/', [PengaturanAbsensiController::class, 'update'])->name('update');
    });

    // Kantor
    Route::resource('kantor', KantorController::class)->middleware('check.permission:kantor.index');

    // Jenis Izin
    Route::resource('jenis-izin', JenisIzinController::class)->middleware('check.permission:jenis-izin.index');

    // Pegawai
    Route::resource('pegawai', PegawaiController::class)->middleware('check.permission:pegawai.index');

    // Hari Libur
    Route::post('hari-libur/sync', [HariLiburController::class, 'sync'])->name('hari-libur.sync')->middleware('check.permission:hari-libur.index');
    Route::resource('hari-libur', HariLiburController::class)->middleware('check.permission:hari-libur.index');
    Route::get('api/hari-libur/events', [HariLiburController::class, 'getEvents'])->name('api.hari-libur.events');

    // Product & Mitra Module (DATA MASTER)
    Route::prefix('master')->group(function () {
        // Product Management
        Route::get('products/template', [ProductsController::class, 'downloadTemplate'])->name('products.template');
        Route::post('products/import', [ProductsController::class, 'import'])->name('products.import');
        Route::resource('product-category', ProductCategoryController::class);
        Route::resource('product-sub-category', ProductSubCategoryController::class);
        Route::resource('products', ProductsController::class);
        Route::get('api/product-sub-categories/by-category/{categoryId}', [ProductSubCategoryController::class, 'getByCategory']);

        // Mitra Management
        Route::get('mitra/template', [MitraController::class, 'downloadTemplate'])->name('mitra.template');
        Route::post('mitra/import', [MitraController::class, 'import'])->name('mitra.import');
        Route::resource('mitra-category', MitraCategoryController::class);
        Route::resource('mitra', MitraController::class);

        // Wilayah (Regional Data)
        Route::get('wilayah/provinces', [WilayahController::class, 'provinces'])->name('wilayah.provinces');
        Route::get('wilayah/regencies/{provinceCode}', [WilayahController::class, 'regencies'])->name('wilayah.regencies');
        Route::get('wilayah/districts/{regencyCode}', [WilayahController::class, 'districts'])->name('wilayah.districts');
        Route::post('wilayah/sync-provinces', [WilayahController::class, 'syncProvinces'])->name('wilayah.sync-provinces');
        Route::post('wilayah/sync-regencies', [WilayahController::class, 'syncRegencies'])->name('wilayah.sync-regencies');
        Route::post('wilayah/sync-districts', [WilayahController::class, 'syncDistricts'])->name('wilayah.sync-districts');
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

    // ============== AKTIVITAS ==============
    Route::prefix('aktivitas')->name('aktivitas.')->group(function () {
        // Kunjungan (QC Visit)
        Route::prefix('kunjungan')->name('kunjungan.')->group(function () {
            // Admin melihat semua kunjungan
            Route::prefix('admin')->name('admin.')->middleware('check.permission:kunjungan.admin')->group(function () {
                Route::get('/', [KunjunganController::class, 'adminIndex'])->name('index');
                Route::get('/export', [KunjunganController::class, 'adminExport'])->name('export');
                Route::get('/{kunjungan}', [KunjunganController::class, 'adminShow'])->name('show');
                Route::delete('/{kunjungan}', [KunjunganController::class, 'adminDestroy'])->name('destroy');
            });

            // Form kunjungan baru (Menu: Kunjungan → /aktivitas/kunjungan)
            Route::get('/', [KunjunganController::class, 'create'])->name('create');
            Route::post('/', [KunjunganController::class, 'store'])->name('store');
            Route::get('/{kunjungan}', [KunjunganController::class, 'show'])->name('show');
        });

        // Riwayat Kunjungan (Menu: Riwayat Kunjungan → /aktivitas/riwayat)
        Route::get('/riwayat', [KunjunganController::class, 'index'])->name('riwayat.index');

        // ============== PRODUKSI & STOK ==============
        Route::resource('produksi', ProduksiController::class);
    });

    // ============== PROFILE ==============
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // ============== INFORMASI ==============
    Route::resource('informasi', InformasiController::class)
        ->middleware('check.permission:informasi');

    // ============== BACKUP ==============
    Route::prefix('backup')->name('backup.')->middleware('check.permission:backup')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/export', [BackupController::class, 'export'])->name('export');
    });

    // ============== PENJUALAN ==============
    Route::prefix('penjualan')->group(function () {
        // Dashboard Penjualan
        Route::get('dashboard', [SalesDashboardController::class, 'index'])
            ->name('sales-dashboard.index')
            ->middleware('check.permission:sales-dashboard.index');

        // Sales Order (Draft -> Submit)
        Route::post('sales-order/{id}/submit', [SalesOrderController::class, 'submit'])->name('sales-order.submit');
        Route::get('sales-order/{id}/print', [SalesOrderController::class, 'printPdf'])->name('sales-order.print');
        Route::resource('sales-order', SalesOrderController::class)
            ->middleware('check.permission:sales-order.index');

        // Kelola Order (Warehouse: Approve, Edit, Reject)
        Route::post('kelola-order/{id}/approve', [SalesOrderManageController::class, 'approve'])->name('sales-order.approve');
        Route::post('kelola-order/{id}/reject', [SalesOrderManageController::class, 'reject'])->name('sales-order.reject');
        Route::resource('kelola-order', SalesOrderManageController::class)
            ->parameters(['kelola-order' => 'id'])
            ->names('sales-order.manage')
            ->except(['create', 'store', 'destroy'])
            ->middleware('check.permission:sales-order.manage');

        // Delivery Order (HRD Reassign & Kurir Upload)
        Route::post('delivery-order/{id}/reassign', [DeliveryOrderController::class, 'reassign'])->name('delivery-order.reassign');
        Route::post('delivery-order/{id}/start', [DeliveryOrderController::class, 'start'])->name('delivery-order.start');
        Route::post('delivery-order/{id}/upload-proof', [DeliveryOrderController::class, 'uploadProof'])->name('delivery-order.upload-proof');
        Route::post('delivery-order/{id}/complete-pickup', [DeliveryOrderController::class, 'completePickup'])->name('delivery-order.complete-pickup');
        Route::get('delivery-order/{id}/print', [DeliveryOrderController::class, 'printPdf'])->name('delivery-order.print');
        Route::resource('delivery-order', DeliveryOrderController::class)
            ->parameters(['delivery-order' => 'id'])
            ->only(['index', 'show'])
            ->middleware('check.permission:delivery-order.index');

        // Invoice
        Route::post('invoice/{id}/status', [InvoiceController::class, 'updateStatus'])->name('invoice.update-status')->middleware('check.permission:invoice.index');
        Route::get('invoice/{id}/print', [InvoiceController::class, 'printPdf'])->name('invoice.print')->middleware('check.permission:invoice.index');
        Route::resource('invoice', InvoiceController::class)
            ->parameters(['invoice' => 'id'])
            ->only(['index', 'show'])
            ->middleware('check.permission:invoice.index');
    });
});

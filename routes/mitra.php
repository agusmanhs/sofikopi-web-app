<?php

use App\Http\Controllers\MitraPos\MitraDashboardController;
use App\Http\Controllers\MitraPos\MitraMaterialController;
use App\Http\Controllers\MitraPos\MitraProductController;
use App\Http\Controllers\MitraPos\MitraStockController;
use App\Http\Controllers\MitraPos\PosController;
use App\Http\Controllers\MitraPos\PosTransactionController;
use App\Models\Mitra;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mitra POS routes
|--------------------------------------------------------------------------
| Registered from bootstrap/app.php's withRouting(then:) closure under the
| 'web' middleware group. See CLAUDE.md / plan doc for the RBAC + tenancy
| model: `mitra.user` gates the tenant portal, `mitra.scope` gates the
| Sofikopi-staff admin-setup routes.
*/

// Tenant portal — mitra users only.
Route::middleware(['auth', 'mitra.user'])->prefix('mitra-pos')->group(function () {
    Route::get('dashboard', [MitraDashboardController::class, 'index'])
        ->name('mitra-dashboard.index')
        ->middleware('check.permission:mitra-dashboard.index');

    // Custom action routes before the "index" route for readability/consistency.
    Route::get('pos/products', [PosController::class, 'products'])
        ->name('pos.products')
        ->middleware('check.permission:pos.index');
    Route::get('pos', [PosController::class, 'index'])
        ->name('pos.index')
        ->middleware('check.permission:pos.index');
    Route::post('pos', [PosController::class, 'store'])
        ->name('pos.store')
        ->middleware('check.permission:pos.index');

    Route::get('transaction', [PosTransactionController::class, 'index'])
        ->name('pos-transaction.index')
        ->middleware('check.permission:pos-transaction.index');
    // transaction_no (e.g. POS/LALLO/20260718/0001) contains literal
    // slashes, so this param needs an explicit '.*' constraint — it's
    // safe here because it's the final segment on this route.
    Route::get('transaction/{transaction}', [PosTransactionController::class, 'show'])
        ->name('pos-transaction.show')
        ->where('transaction', '.*')
        ->middleware('check.permission:pos-transaction.index');

    Route::get('stock', [MitraStockController::class, 'index'])
        ->name('mitra-stock.index')
        ->middleware('check.permission:mitra-stock.index');
});

// Admin setup — mitra picker landing page. No {mitra} route param yet, so
// `mitra.scope` (which expects one to resolve) is intentionally NOT applied
// here. Internal staff only, gated by check.pegawai.status + permission.
Route::middleware(['auth', 'check.pegawai.status'])
    ->get('mitra-pos/manage', function () {
        $mitras = Mitra::aktif()->orderBy('name')->get();

        return view('pages.mitra-pos.manage-picker', compact('mitras'));
    })
    ->name('mitra-pos-manage.index')
    ->middleware('check.permission:mitra-pos-manage.index');

// Admin setup — Sofikopi staff managing a specific mitra's POS config.
Route::middleware(['auth', 'check.pegawai.status', 'mitra.scope'])
    ->prefix('mitra-pos/manage/{mitra}')
    ->group(function () {
        // Custom action route before the material resource.
        Route::post('material/{material}/adjust', [MitraStockController::class, 'adjust'])
            ->name('mitra-material.adjust')
            ->middleware('check.permission:mitra-material.index');

        Route::resource('material', MitraMaterialController::class)
            ->names('mitra-material')
            ->middleware('check.permission:mitra-material.index');

        Route::resource('product', MitraProductController::class)
            ->names('mitra-product')
            ->middleware('check.permission:mitra-product.index');
    });

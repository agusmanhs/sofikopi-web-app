<?php

use App\Http\Controllers\MitraPos\MitraDashboardController;
use App\Http\Controllers\MitraPos\MitraMaterialController;
use App\Http\Controllers\MitraPos\MitraOpnameController;
use App\Http\Controllers\MitraPos\MitraPosManageController;
use App\Http\Controllers\MitraPos\MitraProductController;
use App\Http\Controllers\MitraPos\MitraReportController;
use App\Http\Controllers\MitraPos\MitraSettingController;
use App\Http\Controllers\MitraPos\MitraStockController;
use App\Http\Controllers\MitraPos\PosController;
use App\Http\Controllers\MitraPos\PosTransactionController;
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
    // transaction_no (e.g. POS/LALLO/20260718/0001) contains literal slashes,
    // so every {transaction} param below needs an explicit '.*' constraint.
    // Because that constraint is unanchored, it would otherwise also swallow
    // a literal trailing segment like "/receipt" or "/void" if the bare
    // "transaction/{transaction}" (show) route were registered first — Laravel
    // tries routes in registration order, so the longer/more specific paths
    // (receipt, void) MUST come before the plain show route.
    Route::get('transaction/{transaction}/receipt', [PosTransactionController::class, 'receipt'])
        ->name('pos-transaction.receipt')
        ->where('transaction', '.*')
        ->middleware('check.permission:pos-transaction.index');

    // 'void' -> 'delete' flag per CheckPermission's action map. Kasir has no
    // can_delete pivot on pos-transaction.index, so only the mitra-owner can
    // reach this — see MitraPosMenuSeeder.
    Route::post('transaction/{transaction}/void', [PosTransactionController::class, 'void'])
        ->name('pos-transaction.void')
        ->where('transaction', '.*')
        ->middleware('check.permission:pos-transaction.index');

    Route::get('transaction/{transaction}', [PosTransactionController::class, 'show'])
        ->name('pos-transaction.show')
        ->where('transaction', '.*')
        ->middleware('check.permission:pos-transaction.index');

    Route::get('stock/movements', [MitraStockController::class, 'movements'])
        ->name('mitra-stock.movements')
        ->middleware('check.permission:mitra-stock.index');
    Route::get('stock', [MitraStockController::class, 'index'])
        ->name('mitra-stock.index')
        ->middleware('check.permission:mitra-stock.index');

    Route::get('settings', [MitraSettingController::class, 'index'])
        ->name('mitra-setting.index')
        ->middleware('check.permission:mitra-setting.index');
    Route::put('settings', [MitraSettingController::class, 'update'])
        ->name('mitra-setting.update')
        ->middleware('check.permission:mitra-setting.index');

    // 'export' isn't in CheckPermission's action map -> defaults to 'read'.
    Route::get('report/export', [MitraReportController::class, 'export'])
        ->name('mitra-report.export')
        ->middleware('check.permission:mitra-report.index');
    Route::get('report', [MitraReportController::class, 'index'])
        ->name('mitra-report.index')
        ->middleware('check.permission:mitra-report.index');

    // Literal 'opname/create' MUST be registered before the wildcard
    // 'opname/{opname}' show route below — opname_no contains slashes
    // (matched by '.*'), which would otherwise swallow "create" as if it
    // were an opname_no (same class of bug fixed on the transaction routes).
    Route::get('opname/create', [MitraOpnameController::class, 'create'])
        ->name('mitra-opname.create')
        ->middleware('check.permission:mitra-opname.index');
    Route::get('opname', [MitraOpnameController::class, 'index'])
        ->name('mitra-opname.index')
        ->middleware('check.permission:mitra-opname.index');
    Route::post('opname', [MitraOpnameController::class, 'store'])
        ->name('mitra-opname.store')
        ->middleware('check.permission:mitra-opname.index');
    Route::get('opname/{opname}', [MitraOpnameController::class, 'show'])
        ->name('mitra-opname.show')
        ->where('opname', '.*')
        ->middleware('check.permission:mitra-opname.index');
});

// Admin setup — mitra picker landing page + enroll/de-enroll. No {mitra}
// route param on index/store, so `mitra.scope` (which expects one to
// resolve) is intentionally NOT applied here. Internal staff only, gated
// by check.pegawai.status + permission.
Route::middleware(['auth', 'check.pegawai.status'])->group(function () {
    Route::get('mitra-pos/manage', [MitraPosManageController::class, 'index'])
        ->name('mitra-pos-manage.index')
        ->middleware('check.permission:mitra-pos-manage.index');

    // Enroll an existing mitra into the POS system.
    Route::post('mitra-pos/manage', [MitraPosManageController::class, 'store'])
        ->name('mitra-pos-manage.store')
        ->middleware('check.permission:mitra-pos-manage.index');

    Route::delete('mitra-pos/manage', [MitraPosManageController::class, 'destroyBulk'])
        ->name('mitra-pos-manage.destroy-bulk')
        ->middleware('check.permission:mitra-pos-manage.index');
});

// Admin setup — Sofikopi staff managing a specific mitra's POS config.
Route::middleware(['auth', 'check.pegawai.status', 'mitra.scope'])
    ->prefix('mitra-pos/manage/{mitra}')
    ->group(function () {
        // Remove a mitra from the POS system: wipes its data + de-enrolls it.
        Route::delete('/', [MitraPosManageController::class, 'destroy'])
            ->name('mitra-pos-manage.destroy')
            ->middleware('check.permission:mitra-pos-manage.index');

        // Read/void access to a mitra's POS transactions for Sofikopi staff.
        // Gated by the existing 'mitra-pos-manage.index' permission (the
        // same one guarding this whole picker flow) — super-admin already
        // has full CRUD on it, so void ('delete') works out of the box.
        // Longer paths (receipt, void) registered before the bare show route
        // for the same reason as the portal group above.
        Route::get('transaction', [PosTransactionController::class, 'adminIndex'])
            ->name('mitra-pos-manage.transaction.index')
            ->middleware('check.permission:mitra-pos-manage.index');
        Route::get('transaction/{transaction}/receipt', [PosTransactionController::class, 'adminReceipt'])
            ->name('mitra-pos-manage.transaction.receipt')
            ->where('transaction', '.*')
            ->middleware('check.permission:mitra-pos-manage.index');
        Route::post('transaction/{transaction}/void', [PosTransactionController::class, 'adminVoid'])
            ->name('mitra-pos-manage.transaction.void')
            ->where('transaction', '.*')
            ->middleware('check.permission:mitra-pos-manage.index');
        Route::get('transaction/{transaction}', [PosTransactionController::class, 'adminShow'])
            ->name('mitra-pos-manage.transaction.show')
            ->where('transaction', '.*')
            ->middleware('check.permission:mitra-pos-manage.index');

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

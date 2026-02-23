<?php

use App\Http\Controllers\Api\UserApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\AbsensiApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);
    
    // Absensi Routes
    Route::prefix('absensi')->group(function () {
        Route::get('/history', [AbsensiApiController::class, 'history']);
        Route::post('/masuk', [AbsensiApiController::class, 'masuk']);
        Route::post('/pulang', [AbsensiApiController::class, 'pulang']);
    });

    // Profile Routes
    Route::prefix('profile')->group(function () {
        Route::post('/update', [\App\Http\Controllers\Api\ProfileApiController::class, 'update']);
        Route::post('/password', [\App\Http\Controllers\Api\ProfileApiController::class, 'updatePassword']);
    });

    // Informasi Routes
    Route::prefix('informasi')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\InformasiApiController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\InformasiApiController::class, 'show']);
    });
});

// User API Routes
Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [UserApiController::class, 'index']);
    Route::get('/{id}', [UserApiController::class, 'show']);
});

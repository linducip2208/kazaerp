<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'token'])->middleware('throttle:10,1');
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/invoices', [\App\Http\Controllers\Api\ResourceController::class, 'invoices']);
        Route::get('/stock', [\App\Http\Controllers\Api\ResourceController::class, 'stock']);
        Route::get('/products', [\App\Http\Controllers\Api\ResourceController::class, 'products']);
        Route::get('/products/{id}/price', [\App\Http\Controllers\Api\ResourceController::class, 'productPrice']);
        Route::get('/customers', [\App\Http\Controllers\Api\ResourceController::class, 'customers']);
        // POS mobile (per-shift, harga tier bila customer dipilih)
        Route::get('/pos/shift', [\App\Http\Controllers\Api\PosController::class, 'shift']);
        Route::post('/pos/shift/open', [\App\Http\Controllers\Api\PosController::class, 'openShift']);
        Route::post('/pos/shift/close', [\App\Http\Controllers\Api\PosController::class, 'closeShift']);
        Route::post('/pos/checkout', [\App\Http\Controllers\Api\PosController::class, 'checkout']);
        // GPS transport
        Route::get('/trips/{tripId}/positions', [\App\Http\Controllers\Api\TransportController::class, 'index']);
        Route::post('/trips/{tripId}/positions', [\App\Http\Controllers\Api\TransportController::class, 'store']);
    });
});

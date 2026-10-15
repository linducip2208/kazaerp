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
    });
});

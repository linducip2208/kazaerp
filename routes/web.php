<?php
use Illuminate\Support\Facades\Route;
Route::get('/up', fn() => response()->json(['ok'=>true]));
Route::get('/login', [\App\Http\Controllers\AuthController::class,'showLogin'])->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class,'login'])->name('login.attempt')->middleware('throttle:10,1');
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class,'showForgot'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class,'sendLink'])->name('password.email')->middleware('throttle:5,1');
Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class,'showReset'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class,'doReset'])->name('password.update');
Route::post('/logout', [\App\Http\Controllers\AuthController::class,'logout'])->name('logout')->middleware('auth');
Route::middleware(['auth','ctx'])->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class,'index'])->name('dashboard');
    Route::get('/health', [\App\Http\Controllers\DashboardController::class,'health'])->name('health');
    Route::get('/profile', [\App\Http\Controllers\AuthController::class,'profile'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\AuthController::class,'updateProfile'])->name('profile.update');
    Route::post('/context', [\App\Http\Controllers\AuthController::class,'switchContext'])->name('context.switch');
});
foreach (glob(__DIR__.'/modules/*.php') as $f) require $f;

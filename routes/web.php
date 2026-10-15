<?php
use Illuminate\Support\Facades\Route;
Route::get('/up', fn() => response()->json(['ok'=>true]));
Route::get('/login', [\App\Http\Controllers\AuthController::class,'showLogin'])->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class,'login'])->name('login.attempt')->middleware('throttle:10,1');
Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class,'showForgot'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class,'sendLink'])->name('password.email')->middleware('throttle:5,1');
Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class,'showReset'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class,'doReset'])->name('password.update');
Route::get('/2fa/challenge', [\App\Http\Controllers\TwoFactorController::class,'challenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [\App\Http\Controllers\TwoFactorController::class,'verify'])->name('2fa.verify')->middleware('throttle:10,1');
Route::post('/logout', [\App\Http\Controllers\AuthController::class,'logout'])->name('logout')->middleware('auth');
Route::middleware(['auth','ctx'])->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class,'index'])->name('dashboard');
    Route::get('/health', [\App\Http\Controllers\DashboardController::class,'health'])->name('health');
    Route::get('/profile', [\App\Http\Controllers\AuthController::class,'profile'])->name('profile');
    Route::put('/profile', [\App\Http\Controllers\AuthController::class,'updateProfile'])->name('profile.update');
    Route::post('/context', [\App\Http\Controllers\AuthController::class,'switchContext'])->name('context.switch');
    Route::get('/2fa/setup', [\App\Http\Controllers\TwoFactorController::class,'setup'])->name('2fa.setup');
    Route::post('/2fa/confirm', [\App\Http\Controllers\TwoFactorController::class,'confirm'])->name('2fa.confirm');
    Route::post('/2fa/disable', [\App\Http\Controllers\TwoFactorController::class,'disable'])->name('2fa.disable');
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class,'index'])->name('notifications.index');
    Route::get('/notifications/{id}', [\App\Http\Controllers\NotificationController::class,'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class,'readAll'])->name('notifications.readAll');
    Route::get('/files', [\App\Http\Controllers\SecureFileController::class,'index'])->name('files.index')->middleware('perm:crm.view');
    Route::post('/files', [\App\Http\Controllers\SecureFileController::class,'store'])->name('files.store')->middleware('perm:crm.create');
    Route::get('/files/{id}/download', [\App\Http\Controllers\SecureFileController::class,'download'])->name('files.download');
});
foreach (glob(__DIR__.'/modules/*.php') as $f) require $f;

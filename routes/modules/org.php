<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','ctx'])->prefix('org')->name('org.')->group(function () {
    Route::resource('holdings', \App\Http\Controllers\Org\HoldingController::class)->middleware('perm:org.view');
    Route::resource('entities', \App\Http\Controllers\Org\LegalEntityController::class)->middleware('perm:org.view');
    Route::resource('branches', \App\Http\Controllers\Org\BranchController::class)->middleware('perm:org.view');
    Route::resource('warehouses', \App\Http\Controllers\Org\WarehouseController::class)->middleware('perm:org.view');
});
Route::middleware(['auth','ctx'])->group(function () {
    Route::resource('users', \App\Http\Controllers\Identity\UserController::class)->middleware('perm:users.view');
    Route::get('users/{id}/roles', [\App\Http\Controllers\Identity\UserController::class,'editRoles'])->name('users.roles')->middleware('perm:roles.assign');
    Route::post('users/{id}/roles', [\App\Http\Controllers\Identity\UserController::class,'updateRoles'])->name('users.roles.update')->middleware('perm:roles.assign');
    Route::post('users/{id}/scopes', [\App\Http\Controllers\Identity\UserController::class,'updateScopes'])->name('users.scopes.update')->middleware('perm:roles.assign');
    Route::post('users/{id}/toggle', [\App\Http\Controllers\Identity\UserController::class,'toggle'])->name('users.toggle')->middleware('perm:users.deactivate');
    Route::resource('roles', \App\Http\Controllers\Identity\RoleController::class)->middleware('perm:roles.view');
    Route::get('roles/{id}/perms', [\App\Http\Controllers\Identity\RoleController::class,'perms'])->name('roles.perms')->middleware('perm:roles.assign');
    Route::post('roles/{id}/perms', [\App\Http\Controllers\Identity\RoleController::class,'updatePerms'])->name('roles.perms.update')->middleware('perm:roles.assign');
});

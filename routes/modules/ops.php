<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','ctx'])->prefix('inventory')->name('inventory.')->group(function () {
    Route::get('stock', [\App\Http\Controllers\Inventory\StockController::class,'index'])->name('stock.index')->middleware('perm:inventory.view');
    Route::get('stock/export', [\App\Http\Controllers\Inventory\StockController::class,'export'])->name('stock.export')->middleware('perm:reports.export');
    Route::get('stock/{id}/card', [\App\Http\Controllers\Inventory\StockController::class,'card'])->name('stock.card')->middleware('perm:inventory.view');
    Route::get('receipts', [\App\Http\Controllers\Inventory\ReceiptController::class,'index'])->name('receipts.index')->middleware('perm:inventory.view');
    Route::get('receipts/create', [\App\Http\Controllers\Inventory\ReceiptController::class,'create'])->name('receipts.create')->middleware('perm:inventory.receive');
    Route::post('receipts', [\App\Http\Controllers\Inventory\ReceiptController::class,'store'])->name('receipts.store')->middleware('perm:inventory.receive');
});
Route::middleware(['auth','ctx'])->prefix('purchasing')->name('purchasing.')->group(function () {
    Route::get('requisitions', [\App\Http\Controllers\Purchasing\RequisitionController::class,'index'])->name('requisitions.index')->middleware('perm:purchasing.view');
    Route::get('requisitions/create', [\App\Http\Controllers\Purchasing\RequisitionController::class,'create'])->name('requisitions.create')->middleware('perm:purchasing.create');
    Route::post('requisitions', [\App\Http\Controllers\Purchasing\RequisitionController::class,'store'])->name('requisitions.store')->middleware('perm:purchasing.create');
    Route::resource('orders', \App\Http\Controllers\Purchasing\PurchaseOrderController::class)->middleware('perm:purchasing.view');
    Route::post('orders/{id}/approve', [\App\Http\Controllers\Purchasing\PurchaseOrderController::class,'approve'])->name('orders.approve')->middleware('perm:purchasing.approve');
    Route::get('vinvoices', [\App\Http\Controllers\Purchasing\VendorInvoiceController::class,'index'])->name('vinvoices.index')->middleware('perm:purchasing.view');
    Route::get('vinvoices/create', [\App\Http\Controllers\Purchasing\VendorInvoiceController::class,'create'])->name('vinvoices.create')->middleware('perm:purchasing.create');
    Route::post('vinvoices', [\App\Http\Controllers\Purchasing\VendorInvoiceController::class,'store'])->name('vinvoices.store')->middleware('perm:purchasing.create');
});

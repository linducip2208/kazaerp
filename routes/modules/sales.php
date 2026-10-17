<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','ctx'])->prefix('sales')->name('sales.')->group(function () {
    Route::resource('quotes', \App\Http\Controllers\Sales\QuotationController::class)->middleware('perm:sales.view');
    Route::post('quotes/{id}/approve', [\App\Http\Controllers\Sales\QuotationController::class,'approve'])->name('quotes.approve')->middleware('perm:sales.approve');
    Route::resource('orders', \App\Http\Controllers\Sales\SalesOrderController::class)->middleware('perm:sales.view');
    Route::post('orders/{id}/approve', [\App\Http\Controllers\Sales\SalesOrderController::class,'approve'])->name('orders.approve')->middleware('perm:sales.approve');
    Route::post('orders/{id}/cancel', [\App\Http\Controllers\Sales\SalesOrderController::class,'cancel'])->name('orders.cancel')->middleware('perm:sales.void');
    Route::resource('deliveries', \App\Http\Controllers\Sales\DeliveryOrderController::class)->middleware('perm:sales.view');
    Route::post('deliveries/{id}/approve', [\App\Http\Controllers\Sales\DeliveryOrderController::class,'approve'])->name('deliveries.approve')->middleware('perm:sales.approve');
    Route::resource('invoices', \App\Http\Controllers\Sales\InvoiceController::class)->middleware('perm:sales.view');
    Route::post('invoices/{id}/pay', [\App\Http\Controllers\Sales\InvoiceController::class,'pay'])->name('invoices.pay')->middleware('perm:sales.create');
    Route::get('invoices/{id}/pdf', [\App\Http\Controllers\Sales\InvoiceController::class,'pdf'])->name('invoices.pdf')->middleware('perm:sales.view');
    Route::resource('creditnotes', \App\Http\Controllers\Sales\CreditNoteController::class)->middleware('perm:sales.view');
    Route::post('creditnotes/{id}/approve', [\App\Http\Controllers\Sales\CreditNoteController::class,'approve'])->name('creditnotes.approve')->middleware('perm:sales.approve');
    Route::resource('returns', \App\Http\Controllers\Sales\SalesReturnController::class)->middleware('perm:sales.view');
    Route::post('returns/{id}/approve', [\App\Http\Controllers\Sales\SalesReturnController::class,'approve'])->name('returns.approve')->middleware('perm:sales.approve');
    Route::get('pos', [\App\Http\Controllers\Sales\PosController::class,'index'])->name('pos.index')->middleware('perm:pos.operate');
    Route::post('pos/open', [\App\Http\Controllers\Sales\PosController::class,'openShift'])->name('pos.open')->middleware('perm:pos.shift');
    Route::post('pos/checkout', [\App\Http\Controllers\Sales\PosController::class,'checkout'])->name('pos.checkout')->middleware('perm:pos.operate');
    Route::post('pos/close', [\App\Http\Controllers\Sales\PosController::class,'closeShift'])->name('pos.close')->middleware('perm:pos.shift');
});

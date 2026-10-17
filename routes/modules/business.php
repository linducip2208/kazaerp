<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','ctx'])->prefix('pet')->name('pet.')->group(function () {
    Route::resource('owners', \App\Http\Controllers\Pet\OwnerController::class)->middleware('perm:pet.view');
    Route::resource('pets', \App\Http\Controllers\Pet\PetController::class)->middleware('perm:pet.view');
    Route::resource('vet', \App\Http\Controllers\Pet\VetAppointmentController::class)->middleware('perm:pet.treat');
    Route::resource('grooming', \App\Http\Controllers\Pet\GroomingOrderController::class)->middleware('perm:pet.groom');
    Route::resource('boarding', \App\Http\Controllers\Pet\BoardingReservationController::class)->middleware('perm:pet.board');
    Route::post('boarding/book', [\App\Http\Controllers\Pet\BoardingCheckinController::class,'store'])->name('boarding.book')->middleware('perm:pet.board');
    Route::get('transport/tracking', [\App\Http\Controllers\Pet\TransportTrackingController::class,'index'])->name('transport.tracking')->middleware('perm:pet.transport');
    Route::get('transport/{id}/track', [\App\Http\Controllers\Pet\TransportTrackingController::class,'show'])->name('transport.track')->middleware('perm:pet.transport');
    Route::resource('transport', \App\Http\Controllers\Pet\TransportOrderController::class)->middleware('perm:pet.transport');
});
Route::middleware(['auth','ctx'])->prefix('mfg')->name('mfg.')->group(function () {
    Route::resource('designs', \App\Http\Controllers\Mfg\DesignController::class)->middleware('perm:mfg.view');
    Route::resource('boms', \App\Http\Controllers\Mfg\BomController::class)->middleware('perm:mfg.view');
    Route::resource('workorders', \App\Http\Controllers\Mfg\WorkOrderController::class)->middleware('perm:mfg.plan');
    Route::post('workorders/{id}/issue', [\App\Http\Controllers\Mfg\WorkOrderController::class,'issue'])->name('workorders.issue')->middleware('perm:mfg.execute');
    Route::post('workorders/{id}/produce', [\App\Http\Controllers\Mfg\WorkOrderController::class,'produce'])->name('workorders.produce')->middleware('perm:mfg.execute');
});
Route::middleware(['auth','ctx'])->prefix('thrift')->name('thrift.')->group(function () {
    Route::resource('lots', \App\Http\Controllers\Thrift\LotController::class)->middleware('perm:thrift.view');
    Route::post('lots/{id}/allocate', [\App\Http\Controllers\Thrift\LotController::class,'allocate'])->name('lots.allocate')->middleware('perm:thrift.manage');
    Route::resource('items', \App\Http\Controllers\Thrift\ItemController::class)->middleware('perm:thrift.view');
    Route::post('live/reserve', [\App\Http\Controllers\Thrift\LiveOrderController::class,'reserve'])->name('live.reserve')->middleware('perm:thrift.live');
});
Route::middleware(['auth','ctx'])->prefix('studio')->name('studio.')->group(function () {
    Route::resource('projects', \App\Http\Controllers\Studio\ProjectController::class)->middleware('perm:studio.view');
});
Route::middleware(['auth','ctx'])->prefix('rental')->name('rental.')->group(function () {
    Route::resource('catalogs', \App\Http\Controllers\Rental\CatalogController::class)->middleware('perm:rental.view');
    Route::resource('reservations', \App\Http\Controllers\Rental\ReservationController::class)->middleware('perm:rental.view');
    Route::post('book', [\App\Http\Controllers\Rental\RentalBookingController::class,'store'])->name('book')->middleware('perm:rental.manage');
});
Route::middleware(['auth','ctx'])->prefix('marketing')->name('marketing.')->group(function () {
    Route::resource('campaigns', \App\Http\Controllers\Marketing\CampaignController::class)->middleware('perm:marketing.view');
    Route::resource('vouchers', \App\Http\Controllers\Marketing\VoucherController::class)->middleware('perm:marketing.view');
});
Route::middleware(['auth','ctx'])->prefix('gov')->name('gov.')->group(function () {
    Route::get('approvals', [\App\Http\Controllers\Gov\ApprovalController::class,'index'])->name('approvals.index')->middleware('perm:governance.view');
    Route::post('approvals/{id}', [\App\Http\Controllers\Gov\ApprovalController::class,'decide'])->name('approvals.decide');
    Route::get('delegations', [\App\Http\Controllers\Gov\DelegationController::class,'index'])->name('delegations.index')->middleware('perm:governance.view');
    Route::post('delegations', [\App\Http\Controllers\Gov\DelegationController::class,'store'])->name('delegations.store');
    Route::post('delegations/{id}/toggle', [\App\Http\Controllers\Gov\DelegationController::class,'toggle'])->name('delegations.toggle');
});
Route::middleware(['auth','ctx'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('sales', [\App\Http\Controllers\ReportController::class,'sales'])->name('sales')->middleware('perm:reports.view');
    Route::get('inventory', [\App\Http\Controllers\ReportController::class,'inventory'])->name('inventory')->middleware('perm:reports.view');
    Route::get('purchase', [\App\Http\Controllers\ReportController::class,'purchase'])->name('purchase')->middleware('perm:reports.view');
    Route::get('aging', [\App\Http\Controllers\ReportController::class,'aging'])->name('aging')->middleware('perm:reports.view');
    Route::get('import/customers', [\App\Http\Controllers\ImportController::class,'customers'])->name('import.customers')->middleware('perm:crm.create');
    Route::post('import/customers', [\App\Http\Controllers\ImportController::class,'customersStore'])->name('import.customers.store')->middleware('perm:crm.create');
    Route::get('import/products', [\App\Http\Controllers\ImportController::class,'products'])->name('import.products')->middleware('perm:crm.create');
    Route::post('import/products', [\App\Http\Controllers\ImportController::class,'productsStore'])->name('import.products.store')->middleware('perm:crm.create');
    Route::get('import/suppliers', [\App\Http\Controllers\ImportController::class,'suppliers'])->name('import.suppliers')->middleware('perm:crm.create');
    Route::post('import/suppliers', [\App\Http\Controllers\ImportController::class,'suppliersStore'])->name('import.suppliers.store')->middleware('perm:crm.create');
    Route::get('import/template/{name}', [\App\Http\Controllers\ImportController::class,'template'])->name('import.template')->middleware('perm:crm.create');
});

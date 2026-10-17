<?php
use Illuminate\Support\Facades\Route;
Route::middleware(['auth','ctx'])->prefix('master')->name('master.')->group(function () {
    Route::resource('products', \App\Http\Controllers\Master\ProductController::class)->middleware('perm:crm.view');
    Route::resource('categories', \App\Http\Controllers\Master\CategoryController::class)->middleware('perm:crm.view');
});
Route::middleware(['auth','ctx'])->prefix('crm')->name('crm.')->group(function () {
    Route::resource('customers', \App\Http\Controllers\Master\CustomerController::class)->middleware('perm:crm.view');
    Route::resource('suppliers', \App\Http\Controllers\Master\SupplierController::class)->middleware('perm:crm.view');
    Route::resource('leads', \App\Http\Controllers\Master\LeadController::class)->middleware('perm:crm.view');
    Route::resource('opportunities', \App\Http\Controllers\Master\OpportunityController::class)->middleware('perm:crm.view');
    Route::post('leads/{id}/convert', function ($id) {
        $lead = \App\Models\Master\Lead::findOrFail($id);
        $me = auth()->user();
        if (! $me->hasRole('super_admin','holding_owner','executive','auditor') && ! $me->inScope('legal_entity',(int)$lead->legal_entity_id) && (int)$me->legal_entity_id !== (int)$lead->legal_entity_id) abort(404);
        if ($lead->converted_customer_id) return back()->withErrors(['msg'=>'Lead sudah dikonversi.']);
        $dup = \App\Models\Master\Customer::where('legal_entity_id',$lead->legal_entity_id)->where(function($q) use ($lead) {
            $q->where('name',$lead->name);
            if ($lead->phone) $q->orWhere('phone',$lead->phone);
        })->first();
        if ($dup) return back()->withErrors(['msg'=>'Calon duplikat dengan pelanggan '.$dup->code.' — konversi dibatalkan agar tidak duplikasi.']);
        $code = 'CUST-'.date('ym').'-'.str_pad((string)(\App\Models\Master\Customer::where('legal_entity_id',$lead->legal_entity_id)->count()+1),4,'0',STR_PAD_LEFT);
        $c = \App\Models\Master\Customer::create(['legal_entity_id'=>$lead->legal_entity_id,'code'=>$code,'name'=>$lead->name,'phone'=>$lead->phone,'email'=>$lead->email,'tier'=>'retail','sales_owner_id'=>$lead->owner_id,'is_active'=>true]);
        $lead->update(['stage'=>'converted','converted_customer_id'=>$c->id]);
        return redirect()->route('crm.customers.show',$c->id)->with('ok','Lead dikonversi tanpa duplikasi.');
    })->name('leads.convert')->middleware('perm:crm.convert');
});

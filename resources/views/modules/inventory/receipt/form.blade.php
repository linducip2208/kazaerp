@extends('layouts.tabler')
@section('title','Penerimaan Barang')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Penerimaan Baru</h3></div><div class="card-body">
<form method="POST" action="{{ route('inventory.receipts.store') }}">@csrf
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">Gudang</label><select name="warehouse_id" class="form-select">@foreach($warehouses as $x)<option value="{{ $x->id }}">{{ $x->code }} — {{ $x->name }}</option>@endforeach</select></div>
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-4"><label class="form-label">PO (opsional, untuk partial)</label><select name="purchase_order_id" class="form-select"><option value="">— Tanpa PO —</option>@foreach($pos as $p)<option value="{{ $p->id }}">{{ $p->number }}</option>@endforeach</select></div></div>
@include('components.doc-lines-form', ['products'=>$products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'sell_price'=>0])])
<button class="btn btn-primary mt-2">Simpan ke Ledger</button></form></div></div>
@endsection

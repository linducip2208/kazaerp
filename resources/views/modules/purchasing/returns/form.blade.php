@extends('layouts.tabler')
@section('title','Retur Pembelian Baru')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Retur ke Supplier (stok berkurang)</h3></div><div class="card-body">
<form method="POST" action="{{ route('purchasing.returns.store') }}">@csrf
<div class="row">
<div class="mb-3 col-md-4"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select" required>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
<div class="mb-3 col-md-4"><label class="form-label">Gudang asal</label><select name="warehouse_id" class="form-select">@foreach($warehouses as $x)<option value="{{ $x->id }}">{{ $x->code }} — {{ $x->name }}</option>@endforeach</select></div>
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
</div>
@include('components.doc-lines-form', ['products'=>$products])
<button class="btn btn-primary mt-2">Simpan Retur</button></form></div></div>
@endsection

@extends('layouts.tabler')
@section('title','Work Order Baru')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Work Order Baru</h3></div><div class="card-body">
<form method="POST" action="{{ route('mfg.workorders.store') }}">@csrf
<div class="row">
<div class="mb-3 col-md-5"><label class="form-label">BOM (produk jadi)</label>
<select name="bom_id" class="form-select" required>
@foreach($boms as $b)<option value="{{ $b->id }}">v{{ $b->version }} — {{ $b->product }} ({{ $b->sku }})</option>@endforeach
</select></div>
<div class="mb-3 col-md-3"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
<div class="mb-3 col-md-2"><label class="form-label">Qty Rencana</label><input type="number" step="any" min="0.0001" name="qty_planned" class="form-control" value="1" required></div>
<div class="mb-3 col-md-2"><label class="form-label">Gudang</label><select name="warehouse_id" class="form-select">
@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->code }}</option>@endforeach
</select></div>
</div>
<div class="form-hint">Kebutuhan material dihitung otomatis dari BOM × qty rencana (snapshot). Material dikeluarkan lewat aksi <strong>Issue Material</strong>, output dicatat lewat <strong>Produce</strong>.</div>
<div class="mt-3"><button class="btn btn-primary">Simpan</button> <a href="{{ route('mfg.workorders.index') }}" class="btn btn-link">Batal</a></div>
</form></div></div>
@endsection

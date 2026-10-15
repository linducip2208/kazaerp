@extends('layouts.tabler')
@section('title','Import Produk')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Import Produk (CSV, maks 2MB, ada preview validasi)</h3></div>
<div class="card-body">
<form method="POST" enctype="multipart/form-data">@csrf
<input type="file" name="file" class="form-control" accept=".csv,.txt" required>
<button class="btn btn-primary mt-2">Upload & Validasi</button></form>
@if(session('import_errors'))<div class="alert alert-warning mt-3"><strong>Catatan validasi:</strong><ul class="mb-0">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<p class="text-muted mt-2">Format products header: sku,name,sell_price,cost_price</p>
</div></div>
@endsection

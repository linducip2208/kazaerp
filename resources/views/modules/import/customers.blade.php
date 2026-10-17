@extends('layouts.tabler')
@section('title','Import Pelanggan')
@section('content')
<div class="page-header"><div class="row align-items-center">
<div class="col"><h2 class="page-title">Import Pelanggan</h2><div class="text-muted">CSV, maks 2 MB, maks 2000 baris/file · duplikat kode dilewati (idempotent)</div></div>
<div class="col-auto"><a href="{{ route('reports.import.template','customers') }}" class="btn btn-outline-primary">Unduh Template CSV</a></div>
</div></div>
<div class="card mt-2"><div class="card-body">
@if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
<form method="POST" enctype="multipart/form-data">@csrf
<div class="mb-2"><label class="form-label" for="file">File CSV</label><input type="file" id="file" name="file" class="form-control" accept=".csv,.txt" required></div>
<button class="btn btn-primary">Upload &amp; Validasi</button></form>
@if(session('import_errors'))<div class="alert alert-warning mt-3"><strong>Catatan validasi:</strong><ul class="mb-0">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<p class="text-muted mt-3 mb-0">Header wajib: <code>code,name</code> (opsional: <code>phone,email</code>).<br>Contoh: <code>C-001,PT Maju Jaya,0812xxxx,admin@maju.test</code></p>
</div></div>
@endsection

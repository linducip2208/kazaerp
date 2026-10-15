@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">{{ $meta['title'] }} Baru</h3></div>
<div class="card-body">
<form method="POST" action="{{ route('sales.invoices.store') }}">@csrf
<div class="row">
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
<div class="mb-3 col-md-4"><label class="form-label">Jatuh Tempo (invoice)</label><input type="date" name="due_date" class="form-control"></div>
<div class="mb-3 col-md-4"><label class="form-label">Pelanggan</label><select name="customer_id" class="form-select" required>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
</div>
@include('components.doc-lines-form', ['products'=>$products])
<div class="mt-3"><button class="btn btn-primary">Simpan</button></div>
</form></div></div>
@endsection

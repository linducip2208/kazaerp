@extends('layouts.tabler')
@section('title','Bayar Utang')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Bayar Vendor Invoice @if($vinv){{ $vinv->number }} (sisa {{ idr($vinv->grand_total - $vinv->paid_total) }})@endif</h3></div>
<div class="card-body">
<form method="POST" action="{{ route('purchasing.appayments.store') }}">@csrf
<input type="hidden" name="vendor_invoice_id" value="{{ $vinv->id ?? '' }}">
<div class="row">
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-4"><label class="form-label">Nominal</label><input type="number" step="any" name="amount" class="form-control" required></div>
<div class="mb-3 col-md-4"><label class="form-label">Metode</label><select name="method" class="form-select"><option value="transfer">Transfer</option><option value="cash">Tunai</option><option value="giro">Giro</option></select></div>
<div class="mb-3 col-md-6"><label class="form-label">Referensi</label><input name="reference" class="form-control"></div>
</div>
<button class="btn btn-primary">Simpan (jurnal utang→kas otomatis)</button></form></div></div>
@endsection

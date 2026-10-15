@extends('layouts.tabler')
@section('title','Invoice Vendor')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Invoice Vendor dari PO @if($po){{ $po->number }}@endif</h3></div><div class="card-body">
<form method="POST" action="{{ route('purchasing.vinvoices.store') }}">@csrf
<input type="hidden" name="purchase_order_id" value="{{ $po->id ?? '' }}">
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">No. Invoice Vendor (ref)</label><input name="vendor_ref" class="form-control" required></div>
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-4"><label class="form-label">Jatuh Tempo</label><input type="date" name="due_date" class="form-control"></div></div>
@if(isset($lines))<table class="table"><thead><tr><th>Produk</th><th>Qty PO</th></tr></thead><tbody>@foreach($lines as $l)<tr><td>{{ $l->name }}</td><td>{{ $l->qty }}</td></tr>@endforeach</tbody></table>@endif
<button class="btn btn-primary">Simpan (auto three-way matching)</button></form></div></div>
@endsection

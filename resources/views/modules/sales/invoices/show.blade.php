@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center">
<div class="col"><h3 class="card-title">{{ $meta['title'] }} — {{ $row->number }}</h3>
<div class="text-muted">{{ tgl_id($row->date) }} • <span class="badge bg-blue">{{ $row->status }}</span></div></div>
<div class="col-auto no-print"><a href="{{ route('sales.invoices.pdf',$row->id) }}" class="btn btn-outline-primary">PDF</a>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#payModal">Bayar</button>
<div class="modal fade" id="payModal"><div class="modal-dialog"><form method="POST" action="{{ route('sales.invoices.pay',$row->id) }}" class="modal-content">@csrf
<input type="hidden" name="idempotency_key" value="{{ $payToken ?? '' }}">
<div class="modal-header"><h5 class="modal-title">Catat Pembayaran</h5></div>
<div class="modal-body"><div class="mb-2"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-2"><label class="form-label">Nominal (sisa: {{ idr($row->grand_total-$row->paid_total) }})</label><input type="number" step="any" name="amount" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Metode</label><select name="method" class="form-select"><option value="cash">Tunai</option><option value="transfer">Transfer</option><option value="deposit">Deposit</option></select></div></div>
<div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div></div></div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Total</th></tr></thead>
<tbody>@foreach($row->lines ?? $lines ?? [] as $l)<tr><td>{{ $l->snapshot_name ?? $l->product->name ?? '-' }}</td><td class="text-end">{{ $l->qty }}</td><td class="text-end">{{ idr($l->price) }}</td><td class="text-end">{{ idr($l->line_total) }}</td></tr>@endforeach</tbody>
<tfoot><tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end">{{ idr($row->subtotal ?? 0) }}</th></tr>
<tr><th colspan="3" class="text-end">Pajak</th><th class="text-end">{{ idr($row->tax ?? 0) }}</th></tr>
<tr><th colspan="3" class="text-end">Grand Total</th><th class="text-end">{{ idr($row->grand_total ?? 0) }}</th></tr></tfoot></table></div></div>
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Pembayaran</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Metode</th><th class="text-end">Nominal</th></tr></thead>
<tbody>@foreach($row->payments as $p)<tr><td>{{ tgl_id($p->date) }}</td><td>{{ $p->method }}</td><td class="text-end">{{ idr($p->amount) }}</td></tr>@endforeach</tbody></table></div></div>
@endsection

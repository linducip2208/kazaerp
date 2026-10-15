@extends('layouts.tabler')
@section('title','Purchase Order')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center">
<div class="col"><h3 class="card-title">PO {{ $row->number }} — <span class="badge bg-blue">{{ $row->status }}</span></h3></div>
<div class="col-auto"><form method="POST" action="{{ route('purchasing.orders.approve',$row->id) }}">@csrf<button class="btn btn-primary">Setujui</button></form>
<a href="{{ route('purchasing.vinvoices.create') }}?from_po={{ $row->id }}" class="btn btn-outline-primary">Buat Invoice Vendor</a>
<a href="{{ route('inventory.receipts.create') }}" class="btn btn-outline-secondary">Terima Barang</a></div></div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Diterima</th><th class="text-end">Harga</th></tr></thead>
<tbody>@foreach($row->lines as $l)<tr><td>{{ $l->product->name ?? '-' }}</td><td class="text-end">{{ $l->qty }}</td><td class="text-end">{{ $l->qty_received }}</td><td class="text-end">{{ idr($l->price) }}</td></tr>@endforeach</tbody></table></div></div>
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Invoice Vendor Terkait</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Status</th><th>Match</th></tr></thead>
<tbody>@forelse($invoices as $v)<tr><td>{{ $v->number }}</td><td>{{ $v->status }}</td><td>{{ $v->match_status }}</td></tr>@empty<tr><td colspan="3"><div class="empty-state">Belum ada invoice.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection

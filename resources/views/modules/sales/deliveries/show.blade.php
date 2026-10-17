@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center">
<div class="col"><h3 class="card-title">{{ $meta['title'] }} — {{ $row->number }}</h3>
<div class="text-muted">{{ tgl_id($row->date) }} • @include('components.status-badge',['status'=>$row->status])</div></div>
<div class="col-auto no-print">@if(($row->status ?? '')==='draft')<form method="POST" action="{{ route('sales.deliveries.approve',$row->id) }}" class="d-inline">@csrf<button class="btn btn-primary">Setujui</button></form>@endif</div></div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Total</th></tr></thead>
<tbody>@foreach($row->lines ?? $lines ?? [] as $l)<tr><td>{{ $l->snapshot_name ?? $l->product->name ?? '-' }}</td><td class="text-end">{{ $l->qty }}</td><td class="text-end">{{ idr($l->price) }}</td><td class="text-end">{{ idr($l->line_total) }}</td></tr>@endforeach</tbody>
<tfoot><tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end">{{ idr($row->subtotal ?? 0) }}</th></tr>
<tr><th colspan="3" class="text-end">Pajak</th><th class="text-end">{{ idr($row->tax ?? 0) }}</th></tr>
<tr><th colspan="3" class="text-end">Grand Total</th><th class="text-end">{{ idr($row->grand_total ?? 0) }}</th></tr></tfoot></table></div></div>

@endsection

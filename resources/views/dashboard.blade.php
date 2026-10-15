@extends('layouts.tabler')
@section('title','Dashboard')
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">Dashboard</h2><div class="text-muted">Periode berjalan • {{ tgl_id(now(), true) }} WIB</div></div>
</div></div>
<div class="row row-cards mt-2">
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Penjualan Bulan Ini</div><div class="h1">{{ idr($salesMonth) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Piutang Beredar</div><div class="h1">{{ idr($receivable) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Utang Beredar</div><div class="h1">{{ idr($payable) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Stok Menipis / Approval Pending</div><div class="h1">{{ $lowStock }} / {{ $pendingApprovals }}</div></div></div></div>
</div>
@if($perEntity->count())
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Pendapatan per Badan Usaha (bulan ini)</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Badan Usaha</th><th class="text-end">Total</th></tr></thead>
<tbody>@foreach($perEntity as $e)<tr><td>{{ $e->code }}</td><td>{{ $e->name }}</td><td class="text-end">{{ idr($e->total) }}</td></tr>@endforeach</tbody></table></div></div>
@endif
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Sales Order Terbaru</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th></tr></thead>
<tbody>@forelse($orders as $o)<tr><td><a href="{{ route('sales.orders.show',$o->id) }}">{{ $o->number }}</a></td><td>{{ tgl_id($o->date) }}</td><td class="text-end">{{ idr($o->grand_total) }}</td><td><span class="badge bg-blue">{{ $o->status }}</span></td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada order. <a href="{{ route('sales.orders.create') }}">Buat pertama</a>.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection

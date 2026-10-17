@extends('layouts.tabler')
@section('title','Laporan Pembelian')
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">Laporan Pembelian</h2><div class="text-muted">Purchase order per entitas aktif · {{ tgl_id(now()) }}</div></div>
<div class="col-auto ms-auto d-flex gap-1">
<a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor CSV</a>
<a href="?export=pdf&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor PDF</a>
<button class="btn btn-secondary" onclick="window.print()">Cetak</button>
</div></div></div>
<div class="row row-cards mt-2">
<div class="col-sm-6 col-lg-6"><div class="card"><div class="card-body"><div class="text-muted">Purchase Order</div><div class="h2 mb-0">{{ number_format($sum->c ?? 0) }}</div></div></div></div>
<div class="col-sm-6 col-lg-6"><div class="card"><div class="card-body"><div class="text-muted">Total Nilai</div><div class="h2 mb-0">{{ idr($sum->t ?? 0) }}</div></div></div></div>
</div>
<div class="card mt-2"><div class="card-body border-bottom d-print-none">
<form method="GET" class="row g-2">
<div class="col-md-4"><input type="search" name="q" class="form-control" placeholder="Cari nomor PO / supplier…" value="{{ request('q') }}" aria-label="Cari"></div>
<div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}" aria-label="Dari tanggal"></div>
<div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}" aria-label="Sampai tanggal"></div>
<div class="col-md-2"><select name="status" class="form-select" aria-label="Status"><option value="">Semua status</option>@foreach(['draft','submitted','approved','ordered','received','closed','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-secondary w-100">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Nomor</th><th>Tanggal</th><th>Supplier</th><th class="text-end">Subtotal</th><th class="text-end">Pajak</th><th class="text-end">Total</th><th>Status</th></tr></thead>
<tbody>
@forelse($rows as $r)<tr>
<td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td>{{ $r->supplier }}</td>
<td class="text-end">{{ idr($r->subtotal) }}</td><td class="text-end">{{ idr($r->tax) }}</td><td class="text-end">{{ idr($r->grand_total) }}</td>
<td><span class="badge bg-blue">{{ $r->status }}</span></td>
</tr>
@empty<tr><td colspan="7"><div class="empty-state">Tidak ada pembelian pada filter ini. Ubah rentang tanggal atau kata kunci.</div></td></tr>@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer d-print-none">{{ $rows->links() }}</div>@endif
</div>
@endsection

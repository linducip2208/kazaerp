@extends('layouts.tabler')
@section('title','Laporan Inventori')
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">Laporan Mutasi Inventori</h2><div class="text-muted">Kartu mutasi stok per entitas aktif · {{ tgl_id(now()) }}</div></div>
<div class="col-auto ms-auto d-flex gap-1">
<a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor CSV</a>
<a href="?export=pdf&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor PDF</a>
<button class="btn btn-secondary" onclick="window.print()">Cetak</button>
</div></div></div>
<div class="row row-cards mt-2">
<div class="col-sm-6 col-lg-6"><div class="card"><div class="card-body"><div class="text-muted">Baris Mutasi</div><div class="h2 mb-0">{{ number_format($sum->c ?? 0) }}</div></div></div></div>
<div class="col-sm-6 col-lg-6"><div class="card"><div class="card-body"><div class="text-muted">Qty Bersih (masuk − keluar)</div><div class="h2 mb-0">{{ number_format((float)($sum->qty_masuk_keluar_bersih ?? 0), 2) }}</div></div></div></div>
</div>
<div class="card mt-2"><div class="card-body border-bottom d-print-none">
<form method="GET" class="row g-2">
<div class="col-md-4"><input type="search" name="q" class="form-control" placeholder="Cari SKU / produk…" value="{{ request('q') }}" aria-label="Cari"></div>
<div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}" aria-label="Dari tanggal"></div>
<div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}" aria-label="Sampai tanggal"></div>
<div class="col-md-2"><select name="warehouse" class="form-select" aria-label="Gudang"><option value="">Semua gudang</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected((string)request('warehouse')===(string)$w->id)>{{ $w->code }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-secondary w-100">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Waktu</th><th>SKU</th><th>Produk</th><th>Gudang</th><th class="text-end">Qty</th><th>Referensi</th></tr></thead>
<tbody>
@forelse($rows as $r)<tr>
<td>{{ tgl_id($r->created_at, true) }}</td><td>{{ $r->sku }}</td><td>{{ $r->product }}</td><td>{{ $r->wh }}</td>
<td class="text-end">{{ number_format((float)$r->qty, 2) }}</td><td>{{ $r->ref_type }} #{{ $r->ref_id }}</td>
</tr>
@empty<tr><td colspan="6"><div class="empty-state">Tidak ada mutasi pada filter ini. Ubah rentang tanggal atau kata kunci.</div></td></tr>@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer d-print-none">{{ $rows->links() }}</div>@endif
</div>
@endsection

@extends('layouts.tabler')
@section('title','Stok Menua')
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">Stok Lambat / Menua</h2><div class="text-muted">Tanpa mutasi ≥ {{ $days }} hari · {{ tgl_id(now()) }}</div></div>
<div class="col-auto ms-auto d-flex gap-1">
<a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor CSV</a>
<a href="?export=pdf&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor PDF</a>
<button class="btn btn-secondary" onclick="window.print()">Cetak</button>
</div></div></div>
<div class="card mt-2"><div class="card-body border-bottom d-print-none">
<form method="GET" class="row g-2">
<div class="col-md-4"><input type="search" name="q" class="form-control" placeholder="Cari SKU / produk…" value="{{ request('q') }}" aria-label="Cari"></div>
<div class="col-md-3"><select name="warehouse" class="form-select" aria-label="Gudang"><option value="">Semua gudang</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected((string)request('warehouse')===(string)$w->id)>{{ $w->code }}</option>@endforeach</select></div>
<div class="col-md-3"><div class="input-group"><span class="input-group-text">≥</span><input type="number" name="days" class="form-control" value="{{ $days }}" min="1" aria-label="Ambang hari"><span class="input-group-text">hari</span></div></div>
<div class="col-md-2"><button class="btn btn-secondary w-100">Terapkan</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Gudang</th><th>SKU</th><th>Produk</th><th class="text-end">Tersedia</th><th>Mutasi Terakhir</th><th class="text-end">Umur (hari)</th></tr></thead>
<tbody>
@forelse($rows as $r)<tr>
<td>{{ $r->wh }}</td><td>{{ $r->sku }}</td><td>{{ $r->product }}</td>
<td class="text-end">{{ number_format((float)$r->qty_on_hand - (float)$r->qty_reserved, 2) }}</td>
<td>{{ $r->last_move_at ? tgl_id($r->last_move_at) : 'tidak tercatat' }}</td>
<td class="text-end"><span class="badge {{ (int)$r->age_days > 90 ? 'bg-red' : 'bg-yellow' }}">{{ $r->age_days ?? '—' }}</span></td>
</tr>
@empty<tr><td colspan="6"><div class="empty-state">Tidak ada stok menua pada ambang ini. Longgarkan ambang hari atau filter.</div></td></tr>@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer d-print-none">{{ $rows->links() }}</div>@endif
</div>
@endsection

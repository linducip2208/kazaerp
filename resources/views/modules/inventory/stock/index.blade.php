@extends('layouts.tabler')
@section('title','Stok')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Stok</h2></div>
<div class="col-auto"><a href="{{ route('inventory.stock.export') }}" class="btn btn-outline-primary">Export CSV</a>
<a href="{{ route('inventory.receipts.create') }}" class="btn btn-primary">+ Penerimaan</a></div></div></div>
<div class="card mt-2"><div class="card-body border-bottom"><form method="GET" class="row g-2">
<div class="col-md-4"><input name="q" class="form-control" placeholder="Cari SKU/nama…" value="{{ request('q') }}"></div>
<div class="col-md-3"><select name="filter" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="low" @selected(request('filter')==='low')>Menipis (≤5 tersedia)</option><option value="quarantine" @selected(request('filter')==='quarantine')>Karantina</option></select></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Gudang</th><th>SKU</th><th>Produk</th><th class="text-end">On Hand</th><th class="text-end">Reserved</th><th class="text-end">Tersedia</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->wh }}</td><td>{{ $r->sku }}</td><td>{{ $r->product }}</td>
<td class="text-end">{{ $r->qty_on_hand }}</td><td class="text-end">{{ $r->qty_reserved }}</td><td class="text-end">{{ $r->qty_on_hand-$r->qty_reserved }}</td>
<td><a href="{{ route('inventory.stock.card',$r->product_id) }}" class="btn btn-sm btn-outline-primary">Kartu</a></td></tr>
@empty<tr><td colspan="7"><div class="empty-state">Belum ada stok.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

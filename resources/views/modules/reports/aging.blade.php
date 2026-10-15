@extends('layouts.tabler')
@section('title','Stok Menua')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Stok Lambat / Menua (tanpa mutasi ≥ N hari)</h3></div>
<div class="col-auto"><form method="GET" class="d-flex gap-1"><input type="number" name="days" class="form-control form-control-sm" value="{{ $days }}" min="1" style="width:90px"><button class="btn btn-sm btn-secondary">Terapkan</button></form></div></div></div>
<div class="card-body"><div class="table-responsive"><table class="table"><thead><tr><th>Gudang</th><th>SKU</th><th>Produk</th><th class="text-end">Tersedia</th><th>Mutasi Terakhir</th><th class="text-end">Umur (hari)</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->wh }}</td><td>{{ $r->sku }}</td><td>{{ $r->product }}</td><td class="text-end">{{ $r->qty_on_hand - $r->qty_reserved }}</td><td>{{ $r->last_move_at ? tgl_id($r->last_move_at) : '-' }}</td><td class="text-end"><span class="badge {{ $r->age_days > 90 ? 'bg-red' : 'bg-yellow' }}">{{ $r->age_days }}</span></td></tr>
@empty<tr><td colspan="6"><div class="empty-state">Tidak ada stok menua pada ambang ini.</div></td></tr>@endforelse</tbody></table></div></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

@extends('layouts.tabler')
@section('title','Batch & QC')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col">
<h2 class="page-title">Batch & Kendali Mutu (QC)</h2>
<div class="text-muted">Batch berstatus <strong>pending/quarantine/rejected</strong> dan batch kedaluwarsa otomatis diblokir dari pengeluaran stok.</div>
</div></div></div>

<div class="card mt-2">
<div class="card-body">
<form class="row g-2" method="GET">
<div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari lot / SKU / nama produk"></div>
<div class="col-md-3"><select name="qc" class="form-select">
<option value="">Semua status QC</option>
@foreach(['pending'=>'Pending','released'=>'Released','quarantine'=>'Quarantine','rejected'=>'Rejected'] as $k=>$v)
<option value="{{ $k }}" @selected(request('qc')===$k)>{{ $v }} {{ isset($counts[$k]) ? '('.$counts[$k].')' : '' }}</option>
@endforeach
</select></div>
<div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form>
</div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Lot</th><th>Produk</th><th>Kedaluwarsa</th><th class="text-end">On Hand</th><th class="text-end">Reserved</th><th>QC</th><th style="width:360px">Ubah Status QC</th></tr></thead>
<tbody>
@forelse($rows as $r)
<tr>
<td>{{ $r->lot_no }}</td>
<td><div>{{ $r->product }}</div><div class="text-muted small">{{ $r->sku }}</div></td>
<td>
@if($r->expired_at)
{{ tgl_id($r->expired_at) }}
@if($r->expired_at < now()->toDateString())<span class="badge bg-red-lt ms-1">kedaluwarsa</span>@endif
@else<span class="text-muted">—</span>@endif
</td>
<td class="text-end">{{ rtrim(rtrim(number_format((float)$r->qty_on_hand,4,',','.'),'0'),',') }}</td>
<td class="text-end">{{ rtrim(rtrim(number_format((float)$r->qty_reserved,4,',','.'),'0'),',') }}</td>
<td><span class="badge {{ ['released'=>'bg-green-lt','quarantine'=>'bg-yellow-lt','rejected'=>'bg-red-lt','pending'=>'bg-secondary-lt'][$r->qc_status] ?? 'bg-secondary-lt' }}">{{ $r->qc_status }}</span></td>
<td>
@if(auth()->user()->hasPermission('inventory.adjust'))
<form method="POST" action="{{ route('inventory.batches.qc', $r->id) }}" class="row g-1">@csrf
<div class="col-4"><select name="qc_status" class="form-select form-select-sm">
@foreach(['released'=>'Release','quarantine'=>'Quarantine','rejected'=>'Reject','pending'=>'Pending'] as $k=>$v)
<option value="{{ $k }}" @selected($r->qc_status===$k)>{{ $v }}</option>
@endforeach
</select></div>
<div class="col-5"><input name="note" class="form-control form-control-sm" placeholder="Catatan QC (opsional)"></div>
<div class="col-3"><button class="btn btn-sm btn-primary w-100">Simpan</button></div>
</form>
@else<span class="text-muted small">Butuh permission <code>inventory.adjust</code></span>@endif
</td>
</tr>
@empty
<tr><td colspan="7"><div class="empty-state">Belum ada batch. Batch dibuat otomatis saat penerimaan barang mengisi No. Lot.</div></td></tr>
@endforelse
</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

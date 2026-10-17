@extends('layouts.tabler')
@section('title','Laporan Penjualan')
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">Laporan Penjualan</h2><div class="text-muted">Invoice penjualan per entitas aktif · {{ tgl_id(now()) }}</div></div>
<div class="col-auto ms-auto d-flex gap-1">
<a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor CSV</a>
<a href="?export=pdf&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Ekspor PDF</a>
<button class="btn btn-secondary" onclick="window.print()">Cetak</button>
</div></div></div>
<div class="row row-cards mt-2">
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Transaksi</div><div class="h2 mb-0">{{ number_format($sum->c ?? 0) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Total</div><div class="h2 mb-0">{{ idr($sum->t ?? 0) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Terbayar</div><div class="h2 mb-0">{{ idr($sum->p ?? 0) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Sisa</div><div class="h2 mb-0">{{ idr($sum->sisa ?? 0) }}</div></div></div></div>
</div>
<div class="card mt-2"><div class="card-body border-bottom d-print-none">
<form method="GET" class="row g-2">
<div class="col-md-3"><input type="search" name="q" class="form-control" placeholder="Cari nomor invoice / pelanggan…" value="{{ request('q') }}" aria-label="Cari"></div>
<div class="col-md-2"><input type="date" name="from" class="form-control" value="{{ request('from') }}" aria-label="Dari tanggal"></div>
<div class="col-md-2"><input type="date" name="to" class="form-control" value="{{ request('to') }}" aria-label="Sampai tanggal"></div>
<div class="col-md-2"><select name="status" class="form-select" aria-label="Status bayar"><option value="">Semua status</option>@foreach(['unpaid','partial','paid','overdue'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-2"><select name="branch" class="form-select" aria-label="Cabang"><option value="">Semua cabang</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected((string)request('branch')===(string)$b->id)>{{ $b->code }} — {{ $b->name }}</option>@endforeach</select></div>
<div class="col-md-1"><button class="btn btn-secondary w-100">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Nomor</th><th>Tanggal</th><th>Jatuh Tempo</th><th>Pelanggan</th><th class="text-end">Total</th><th class="text-end">Terbayar</th><th>Status</th></tr></thead>
<tbody>
@forelse($rows as $r)<tr>
<td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td>{{ $r->due_date ? tgl_id($r->due_date) : '—' }}</td><td>{{ $r->customer }}</td>
<td class="text-end">{{ idr($r->grand_total) }}</td><td class="text-end">{{ idr($r->paid_total) }}</td>
<td><span class="badge {{ ($r->pay_status ?? '')==='paid' ? 'bg-green' : (($r->pay_status ?? '')==='partial' ? 'bg-yellow' : 'bg-red') }}">{{ $r->pay_status ?? $r->status }}</span></td>
</tr>
@empty<tr><td colspan="7"><div class="empty-state">Tidak ada penjualan pada filter ini. Ubah rentang tanggal atau kata kunci.</div></td></tr>@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer d-print-none">{{ $rows->links() }}</div>@endif
</div>
@endsection

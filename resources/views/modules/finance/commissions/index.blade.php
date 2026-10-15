@extends('layouts.tabler')
@section('title','Komisi Penjualan')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col">
<h2 class="page-title">Komisi Penjualan</h2>
<div class="text-muted">Akrual otomatis saat invoice lunas. Rate dari konfigurasi entitas <code>commission_rate_pct</code>.</div>
</div></div></div>

<div class="row row-cards mt-2">
<div class="col-md-3"><div class="card"><div class="card-body">
<div class="text-muted">Pending</div><div class="h2">{{ idr($totals->pending ?? 0) }}</div></div></div></div>
<div class="col-md-3"><div class="card"><div class="card-body">
<div class="text-muted">Approved</div><div class="h2">{{ idr($totals->approved ?? 0) }}</div></div></div></div>
</div>

<div class="card mt-2">
<div class="card-body"><form class="row g-2" method="GET">
<div class="col-md-3"><select name="status" class="form-select"><option value="">Semua status</option>
@foreach(['pending','approved','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach
</select></div>
<div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Invoice</th><th>Tanggal</th><th>Sales</th><th class="text-end">Basis</th><th class="text-end">Rate</th><th class="text-end">Komisi</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr>
<td>{{ $r->invoice_number ?? '—' }}</td><td>{{ $r->invoice_date ? tgl_id($r->invoice_date) : '—' }}</td>
<td>{{ $r->sales_name }}</td>
<td class="text-end">{{ idr($r->basis_amount) }}</td>
<td class="text-end">{{ (float)$r->rate_pct }}%</td>
<td class="text-end">{{ idr($r->amount) }}</td>
<td><span class="badge {{ ['pending'=>'bg-yellow-lt','approved'=>'bg-green-lt','cancelled'=>'bg-secondary-lt'][$r->status] ?? 'bg-secondary-lt' }}">{{ $r->status }}</span></td>
<td class="text-end">@if($r->status==='pending' && auth()->user()->hasPermission('finance.approve'))
<form method="POST" action="{{ route('finance.commissions.approve',$r->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
<form method="POST" action="{{ route('finance.commissions.cancel',$r->id) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-danger">Batal</button></form>
@endif</td>
</tr>@empty<tr><td colspan="8"><div class="empty-state">Belum ada komisi. Set konfigurasi entitas lebih dulu (minimal rate > 0).</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

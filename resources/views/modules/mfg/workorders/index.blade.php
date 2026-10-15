@extends('layouts.tabler')
@section('title','Work Order')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Work Order Produksi</h2></div>
<div class="col-auto"><a href="{{ route('mfg.workorders.create') }}" class="btn btn-primary">+ Work Order</a></div></div></div>
<div class="card mt-2">
<div class="card-body"><form class="row g-2" method="GET">
<div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nomor / produk"></div>
<div class="col-md-3"><select name="status" class="form-select"><option value="">Semua status</option>
@foreach(['released','in_progress','completed'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach
</select></div>
<div class="col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Nomor</th><th>Tanggal</th><th>Produk</th><th class="text-end">Rencana</th><th class="text-end">Jadi</th><th class="text-end">Reject</th><th class="text-end">Variance</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr>
<td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td>
<td><div>{{ $r->product }}</div><div class="text-muted small">{{ $r->sku }}</div></td>
<td class="text-end">{{ rtrim(rtrim(number_format((float)$r->qty_planned,4,',','.'),'0'),',') }}</td>
<td class="text-end">{{ rtrim(rtrim(number_format((float)$r->qty_good,4,',','.'),'0'),',') }}</td>
<td class="text-end">{{ rtrim(rtrim(number_format((float)$r->qty_reject,4,',','.'),'0'),',') }}</td>
<td class="text-end {{ (float)$r->variance_amount < 0 ? 'text-danger' : 'text-success' }}">{{ idr($r->variance_amount) }}</td>
<td><span class="badge {{ ['released'=>'bg-blue-lt','in_progress'=>'bg-yellow-lt','completed'=>'bg-green-lt'][$r->status] ?? 'bg-secondary-lt' }}">{{ $r->status }}</span></td>
<td><a href="{{ route('mfg.workorders.show',$r->id) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
</tr>@empty<tr><td colspan="9"><div class="empty-state">Belum ada work order.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

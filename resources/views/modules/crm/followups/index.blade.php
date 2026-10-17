@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">{{ $meta['title'] }}</h2></div>
<div class="col-auto d-flex gap-2">
<a href="{{ route($meta['route'].'.index', ['filter' => 'overdue']) }}" class="btn btn-outline-danger">Terlantar</a>
<a href="{{ route($meta['route'].'.index', ['filter' => 'today']) }}" class="btn btn-outline-secondary">Hari ini</a>
<a href="{{ route($meta['route'].'.create') }}" class="btn btn-primary">+ Tambah</a>
</div>
</div></div>
<div class="card mt-2"><div class="card-body border-bottom">
<form method="GET" class="row g-2">
<div class="col-md-4"><input name="q" class="form-control" placeholder="Cari catatan…" value="{{ request('q') }}"></div>
@if(!empty($filter))<input type="hidden" name="filter" value="{{ $filter }}">@endif
<div class="col-md-3"><select name="status" class="form-select" onchange="this.form.submit()"><option value="">— Semua status —</option>
@foreach(['open','done'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>ID</th><th>Induk</th><th>Jatuh Tempo</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($rows as $row)
@php $od = $row->status==='open' && $row->due_at && strtotime($row->due_at) < time(); @endphp
<tr @if($od) class="table-danger" @endif>
<td>#{{ $row->id }}</td>
<td>{{ $row->owner_type === \App\Models\Master\Lead::class ? 'Lead' : 'Pelanggan' }} #{{ $row->owner_id }}</td>
<td>{{ $row->due_at ? tgl_id($row->due_at) : '-' }} @if($od)<span class="badge bg-red">terlambat</span>@endif</td>
<td><span class="badge {{ $row->status==='done' ? 'bg-green' : 'bg-blue' }}">{{ $row->status }}</span></td>
<td class="text-end"><a href="{{ route($meta['route'].'.show',$row->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
</tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada follow-up. Klik <strong>+ Tambah</strong> untuk menjadwalkan.</div></td></tr>
@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection

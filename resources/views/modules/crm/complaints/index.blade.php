@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">{{ $meta['title'] }}</h2></div>
<div class="col-auto"><a href="{{ route($meta['route'].'.create') }}" class="btn btn-primary">+ Tambah</a></div>
</div></div>
<div class="card mt-2"><div class="card-body border-bottom">
<form method="GET" class="row g-2">
<div class="col-md-4"><input name="q" class="form-control" placeholder="Cari subjek…" value="{{ request('q') }}"></div>
<div class="col-md-3"><select name="status" class="form-select" onchange="this.form.submit()"><option value="">— Semua status —</option>
@foreach(['open','in_review','resolved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-3"><select name="urgency" class="form-select" onchange="this.form.submit()"><option value="">— Semua urgensi —</option>
@foreach($urgencies as $v=>$l)<option value="{{ $v }}" @selected(($f_urgency??'')===$v)>{{ $l }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Tiket</th><th>Subjek</th><th>Urgensi</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($rows as $row)<tr>
<td><strong>CMP-{{ str_pad($row->id,6,'0',STR_PAD_LEFT) }}</strong></td>
<td>{{ Str::limit($row->subject,60) }}</td>
<td><span class="badge {{ $row->urgency==='critical' ? 'bg-red' : ($row->urgency==='high' ? 'bg-orange' : 'bg-blue') }}">{{ $row->urgency }}</span></td>
<td><span class="badge {{ in_array($row->status,['resolved']) ? 'bg-green' : ($row->status==='rejected' ? 'bg-dark' : 'bg-blue') }}">{{ $row->status }}</span></td>
<td class="text-end"><a href="{{ route($meta['route'].'.show',$row->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
</tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada tiket. Klik <strong>+ Tambah</strong> untuk mencatat komplain.</div></td></tr>
@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
@endsection

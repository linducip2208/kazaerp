@extends('layouts.tabler')
@section('title','Delegasi Approval')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Delegasi Persetujuan (berbatas tanggal)</h3></div>
<div class="card-body">
<form method="POST" action="{{ route('gov.delegations.store') }}">@csrf
<div class="row">
<div class="mb-2 col-md-3"><label class="form-label">Pemberi (delegator)</label><select name="delegator_id" class="form-select">@foreach($users as $u)<option value="{{ $u->id }}" @selected($u->id==auth()->id())>{{ $u->name }}</option>@endforeach</select></div>
<div class="mb-2 col-md-3"><label class="form-label">Penerima (delegate)</label><select name="delegate_id" class="form-select">@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="mb-2 col-md-2"><label class="form-label">Jenis dok (opsional)</label><input name="doc_type" class="form-control" placeholder="purchase_order"></div>
<div class="mb-2 col-md-2"><label class="form-label">Dari</label><input type="date" name="from_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-2 col-md-2"><label class="form-label">Sampai</label><input type="date" name="to_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
</div>
<button class="btn btn-primary">Simpan Delegasi</button></form>
</div>
<div class="table-responsive"><table class="table"><thead><tr><th>Delegator</th><th>Delegate</th><th>Dok</th><th>Periode</th><th>Aktif</th><th></th></tr></thead>
<tbody>@forelse($rows as $d)<tr><td>{{ $d->delegator }}</td><td>{{ $d->delegate }}</td><td>{{ $d->doc_type ?? 'semua' }}</td><td>{{ $d->from_date }} → {{ $d->to_date }}</td><td>{{ $d->is_active?'Ya':'Tidak' }}</td>
<td><form method="POST" action="{{ route('gov.delegations.toggle',$d->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Toggle</button></form></td></tr>
@empty<tr><td colspan="6"><div class="empty-state">Belum ada delegasi.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

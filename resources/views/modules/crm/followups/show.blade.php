@extends('layouts.tabler')
@section('title', 'Follow-up #'.$row->id)
@section('content')
@if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row">
<div class="col-md-7"><div class="card"><div class="card-header"><h3 class="card-title">Follow-up #{{ $row->id }}</h3>
<span class="badge {{ $row->status==='done' ? 'bg-green' : 'bg-blue' }} ms-2">{{ $row->status }}</span></div>
<div class="card-body">
<p><strong>Induk:</strong> {{ $row->owner_type === \App\Models\Master\Lead::class ? 'Lead' : 'Pelanggan' }} #{{ $row->owner_id }}
@if($owner) — {{ $owner->name ?? '-' }}@endif</p>
<p><strong>Jatuh tempo:</strong> {{ $row->due_at ? tgl_id($row->due_at) : '-' }}</p>
<p><strong>Dibuat:</strong> {{ tgl_id($row->created_at) }}</p>
<p><strong>Catatan:</strong><br>{!! nl2br(e($row->note)) !!}</p>
@if($row->status==='open')
<hr>
<form method="POST" action="{{ route('crm.followups.complete',$row->id) }}">@csrf
<div class="mb-2"><label class="form-label">Hasil percakapan (wajib)</label>
<textarea name="result" class="form-control" rows="2" required></textarea></div>
<button class="btn btn-success">Tandai selesai</button></form>
@endif
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header"><h3 class="card-title">Eskalasi</h3></div>
<div class="card-body">
@if($row->status==='open')
<form method="POST" action="{{ route('crm.followups.escalate',$row->id) }}">@csrf
<div class="mb-2"><label class="form-label">Alihkan ke (user ID)</label>
<input type="number" name="assigned_to" class="form-control" min="1" required></div>
<div class="mb-2"><label class="form-label">Alasan eskalasi</label>
<textarea name="reason" class="form-control" rows="2" required></textarea></div>
<button class="btn btn-warning">Eskalasi</button></form>
@else<p class="text-muted">Follow-up sudah {{ $row->status }}.</p>@endif
<hr><h4>Histori</h4>
@forelse($history as $h)<p class="mb-1"><span class="badge bg-light text-dark">{{ $h->from_status }} → {{ $h->to_status }}</span>
<small class="text-muted">{{ tgl_id($h->created_at) }}</small><br>{{ $h->note }}</p>
@empty<p class="text-muted">Belum ada histori transisi.</p>@endforelse
</div></div></div>
</div>
@endsection

@extends('layouts.tabler')
@section('title', 'Tiket '.$tracking)
@section('content')
@if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="row">
<div class="col-md-7"><div class="card"><div class="card-header"><h3 class="card-title">Tiket {{ $tracking }}</h3>
<span class="badge bg-blue ms-2">{{ $row->status }}</span>
<span class="badge {{ $row->urgency==='critical' ? 'bg-red' : 'bg-orange' }} ms-1">{{ $row->urgency }}</span></div>
<div class="card-body">
<p><strong>Subjek:</strong> {{ $row->subject }}</p>
<p><strong>Isi:</strong><br>{!! nl2br(e($row->body)) !!}</p>
<p><strong>Rujukan:</strong> {{ $row->related_type ? $row->related_type.'#'.$row->related_id : '-' }}</p>
<p><strong>Keputusan:</strong> {{ $row->decision ? ($decisions[$row->decision] ?? $row->decision) : '-' }}</p>
@if($row->resolution)<p><strong>Penyelesaian:</strong><br>{!! nl2br(e($row->resolution)) !!}</p>@endif
@if($row->status==='open')
<hr>
<form method="POST" action="{{ route('crm.complaints.review',$row->id) }}">@csrf
<div class="mb-2"><label class="form-label">Tugaskan ke (user ID)</label>
<input type="number" name="assignee_id" class="form-control" min="1" required></div>
<button class="btn btn-primary">Mulai tangani</button></form>
@endif
@if($row->status==='in_review')
<hr>
<form method="POST" action="{{ route('crm.complaints.resolve',$row->id) }}">@csrf
<div class="mb-2"><label class="form-label">Keputusan</label>
<select name="decision" class="form-select" required>
@foreach($decisions as $v=>$l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select></div>
<div class="mb-2"><label class="form-label">Catatan penyelesaian (wajib)</label>
<textarea name="resolution" class="form-control" rows="2" required></textarea></div>
<button class="btn btn-success">Selesaikan</button></form>
<form method="POST" action="{{ route('crm.complaints.reject',$row->id) }}" class="mt-2">@csrf
<div class="mb-2"><label class="form-label">Alasan penolakan</label>
<textarea name="resolution" class="form-control" rows="2" required></textarea></div>
<button class="btn btn-outline-danger">Tolak beralasan</button></form>
@endif
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header"><h3 class="card-title">Histori transisi</h3></div>
<div class="card-body">
@forelse($history as $h)<p class="mb-1"><span class="badge bg-light text-dark">{{ $h->from_status }} → {{ $h->to_status }}</span>
<small class="text-muted">{{ tgl_id($h->created_at) }}</small><br>{{ $h->note }}</p>
@empty<p class="text-muted">Belum ada histori transisi.</p>@endforelse
</div></div></div>
</div>
@endsection

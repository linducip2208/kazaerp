@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">{{ $meta['title'] }}</h3></div>
<div class="card-body">
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route($meta['route'].'.store') }}">@csrf
<div class="row">
<div class="mb-3 col-md-6"><label class="form-label">Lead (isi salah satu)</label>
<select name="lead_id" class="form-select"><option value="">— Bukan lead —</option>
@foreach($leads as $l)<option value="{{ $l->id }}">{{ $l->name }} ({{ $l->stage }})</option>@endforeach</select></div>
<div class="mb-3 col-md-6"><label class="form-label">Pelanggan (isi salah satu)</label>
<select name="customer_id" class="form-select"><option value="">— Bukan pelanggan —</option>
@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }} [{{ $c->code }}]</option>@endforeach</select></div>
<div class="mb-3 col-md-6"><label class="form-label">Ditugaskan ke</label>
<select name="assigned_to" class="form-select"><option value="">— Saya sendiri —</option>
@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="mb-3 col-md-6"><label class="form-label">Jatuh tempo</label>
<input type="date" name="due_at" class="form-control" value="{{ date('Y-m-d', strtotime('+1 day')) }}" required></div>
<div class="mb-3 col-12"><label class="form-label">Catatan / tindakan berikut</label>
<textarea name="note" class="form-control" rows="3" required></textarea></div>
</div>
<div class="mt-2"><button class="btn btn-primary">Simpan</button>
<a href="{{ route($meta['route'].'.index') }}" class="btn btn-link">Batal</a></div>
</form></div></div>
@endsection

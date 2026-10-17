@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@php $row = $row ?? null; $method = $method ?? 'POST'; $action = $action ?? route($meta['route'].'.store'); @endphp
<div class="card"><div class="card-header"><h3 class="card-title">{{ $meta['title'] }}</h3></div>
<div class="card-body">
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $action }}">@csrf @if($method!=='POST')@method($method)@endif
<div class="row">
<div class="mb-3 col-md-6"><label class="form-label">Pelanggan</label>
<select name="customer_id" class="form-select" required>
@foreach($customers as $c)<option value="{{ $c->id }}" @selected((string)old('customer_id', $row->customer_id ?? '')===(string)$c->id)>{{ $c->name }} [{{ $c->code }}]</option>@endforeach</select></div>
<div class="mb-3 col-md-6"><label class="form-label">Urgensi</label>
<select name="urgency" class="form-select" required>
@foreach($urgencies as $v=>$l)<option value="{{ $v }}" @selected(old('urgency', $row->urgency ?? 'normal')===$v)>{{ $l }}</option>@endforeach</select></div>
<div class="mb-3 col-12"><label class="form-label">Subjek</label>
<input type="text" name="subject" class="form-control" maxlength="150" value="{{ old('subject', $row->subject ?? '') }}" required></div>
<div class="mb-3 col-12"><label class="form-label">Isi laporan</label>
<textarea name="body" class="form-control" rows="3" required>{{ old('body', $row->body ?? '') }}</textarea></div>
<div class="mb-3 col-md-6"><label class="form-label">Rujukan dokumen (opsional, mis. invoices:12)</label>
<input type="text" name="related_type" class="form-control" maxlength="80" value="{{ old('related_type', $row->related_type ?? '') }}" placeholder="invoices"></div>
<div class="mb-3 col-md-6"><label class="form-label">ID rujukan</label>
<input type="number" name="related_id" class="form-control" min="1" value="{{ old('related_id', $row->related_id ?? '') }}"></div>
<div class="mb-3 col-md-6"><label class="form-label">Tugaskan ke (opsional)</label>
<select name="assignee_id" class="form-select"><option value="">— Belum —</option>
@foreach($users as $u)<option value="{{ $u->id }}" @selected((string)old('assignee_id', $row->assignee_id ?? '')===(string)$u->id)>{{ $u->name }}</option>@endforeach</select></div>
</div>
<div class="mt-2"><button class="btn btn-primary">Simpan</button>
<a href="{{ $row ? route($meta['route'].'.show', $row->id) : route($meta['route'].'.index') }}" class="btn btn-link">Batal</a></div>
</form></div></div>
@endsection

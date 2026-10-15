@extends('layouts.tabler')
@section('title','Peran Pengguna')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Peran & Cakupan — {{ $row->name }}</h3></div><div class="card-body">
<form method="POST" action="{{ route('users.roles.update',$row->id) }}">@csrf
<div class="row">@foreach($roles as $r)<div class="col-md-4"><label class="form-check"><input type="checkbox" name="roles[]" value="{{ $r->id }}" class="form-check-input" @checked($row->roles->contains($r->id))><span class="form-check-label">{{ $r->label }}</span></label></div>@endforeach</div>
<button class="btn btn-primary mt-2">Simpan Peran</button></form>
<hr>
<form method="POST" action="{{ route('users.scopes.update',$row->id) }}">@csrf
<div class="row">
<div class="col-md-6"><label class="form-label">Badan usaha tambahan</label><select name="entities[]" class="form-select" multiple size="5">@foreach($entities as $e)<option value="{{ $e->id }}" @selected($scopes->where('scope_type','legal_entity')->contains('scope_id',$e->id))>{{ $e->code }} — {{ $e->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Cabang tambahan</label><select name="branches[]" class="form-select" multiple size="5">@foreach($branches as $b)<option value="{{ $b->id }}" @selected($scopes->where('scope_type','branch')->contains('scope_id',$b->id))>{{ $b->code }} — {{ $b->name }}</option>@endforeach</select></div>
</div>
<button class="btn btn-primary mt-2">Simpan Cakupan</button></form>
<hr>
<form method="POST" action="{{ route('users.toggle',$row->id) }}">@csrf<button class="btn {{ $row->is_active?'btn-outline-danger':'btn-outline-success' }}" onclick="return confirm('Ubah status akun?')">{{ $row->is_active?'Nonaktifkan':'Aktifkan' }}</button></form>
</div></div>
@endsection

@extends('layouts.tabler')
@section('title','Permission Peran')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Permission — {{ $row->label }}</h3></div><div class="card-body">
<form method="POST" action="{{ route('roles.perms.update',$row->id) }}">@csrf
@foreach($groups as $g=>$perms)<h4 class="mt-3">{{ $g }}</h4><div class="row">
@foreach($perms as $p)<div class="col-md-4"><label class="form-check"><input type="checkbox" name="permissions[]" value="{{ $p->id }}" class="form-check-input" @checked(in_array($p->id,$owned))><span class="form-check-label">{{ $p->label }}</span></label></div>@endforeach
</div>@endforeach
<button class="btn btn-primary mt-3">Simpan Permission</button></form>
</div></div>
@endsection

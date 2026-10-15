@extends('layouts.tabler')
@section('title','Profil')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Profil Pengguna</h3></div>
<div class="card-body">
<form method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Nama</label><input name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}"></div>
<div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="{{ auth()->user()->email }}" disabled></div>
<div class="mb-3"><label class="form-label">Peran</label><div>@foreach(auth()->user()->roles as $r)<span class="badge bg-blue me-1">{{ $r->label }}</span>@endforeach</div></div>
<div class="mb-3"><label class="form-label">Keamanan</label><div><a href="{{ route('2fa.setup') }}" class="btn btn-sm btn-outline-primary">Kelola 2FA {{ auth()->user()->two_factor_confirmed_at ? '(Aktif)' : '(Nonaktif)' }}</a></div></div>
<button class="btn btn-primary">Simpan</button>
</form></div></div>
@endsection

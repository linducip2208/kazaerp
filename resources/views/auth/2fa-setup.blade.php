@extends('layouts.tabler')
@section('title','Keamanan 2FA')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Autentikasi Dua Faktor (TOTP)</h3></div>
<div class="card-body">
@if($confirmed)
<div class="alert alert-success">2FA aktif untuk akun Anda.</div>
<form method="POST" action="{{ route('2fa.disable') }}">@csrf
<div class="mb-2 col-md-4"><label class="form-label">Kata sandi (untuk menonaktifkan)</label><input type="password" name="password" class="form-control" required></div>
<button class="btn btn-outline-danger">Nonaktifkan 2FA</button></form>
@else
<p>Pindai QR berikut dengan aplikasi authenticator, lalu masukkan kode 6 digit.</p>
<div class="mb-3">{!! $svg !!}</div>
<form method="POST" action="{{ route('2fa.confirm') }}">@csrf
<div class="mb-2 col-md-3"><label class="form-label">Kode</label><input name="code" class="form-control" inputmode="numeric" required></div>
<button class="btn btn-primary">Aktifkan</button>
<a href="{{ route('2fa.setup') }}?regen=1" class="btn btn-link">Buat kode baru</a></form>
@endif
</div></div>
@endsection

@extends('layouts.auth')
@section('title','Reset Kata Sandi')
@section('heading','Reset kata sandi')
@section('sub','Buat kata sandi baru untuk akun Anda.')
@section('form')
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-lg" value="{{ $email }}" required></div>
<div class="mb-3"><label class="form-label">Kata sandi baru (min 8)</label><input type="password" name="password" class="form-control form-control-lg" required autofocus></div>
<div class="mb-3"><label class="form-label">Konfirmasi</label><input type="password" name="password_confirmation" class="form-control form-control-lg" required></div>
<button class="btn btn-primary btn-lg w-100">Reset</button>
</form>
@endsection

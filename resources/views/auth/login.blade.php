@extends('layouts.auth')
@section('title','Masuk')
@section('heading','Masuk')
@section('sub','Masuk ke ERP terpadu Kazaka Group.')
@section('form')
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('login.attempt') }}">@csrf
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-lg" value="{{ old('email') }}" required autofocus></div>
<div class="mb-2"><label class="form-label">Kata sandi</label><input type="password" name="password" class="form-control form-control-lg" required></div>
<div class="mb-3"><label class="form-check"><input type="checkbox" name="remember" class="form-check-input"><span class="form-check-label">Ingat saya</span></label></div>
<button class="btn btn-primary btn-lg w-100">Masuk</button>
</form>
<p class="text-center mt-3"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
@if(!app()->environment('production') || config('kazaerp.demo_enabled'))
<p class="text-center text-muted mt-2">Akun demo (non-produksi) — Email: <code>admin@kazaka.local</code> · Sandi: <code>Demo#2026</code></p>
@endif
@endsection

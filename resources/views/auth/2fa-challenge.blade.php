@extends('layouts.auth')
@section('title','Verifikasi 2FA')
@section('heading','Kode authenticator')
@section('sub','Masukkan kode 6 digit dari aplikasi authenticator.')
@section('form')
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('2fa.verify') }}">@csrf
<div class="mb-3"><label class="form-label">Kode 6 digit</label><input name="code" class="form-control form-control-lg text-center" inputmode="numeric" autocomplete="one-time-code" required autofocus></div>
<button class="btn btn-primary btn-lg w-100">Verifikasi</button>
</form>
@endsection

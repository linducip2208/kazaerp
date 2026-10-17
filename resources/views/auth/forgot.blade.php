@extends('layouts.auth')
@section('title','Lupa Kata Sandi')
@section('heading','Lupa kata sandi')
@section('sub','Kami kirimkan tautan reset ke email Anda.')
@section('form')
@if(session('ok'))<div class="alert alert-success">{{ session('ok') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('password.email') }}">@csrf
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control form-control-lg" required autofocus></div>
<button class="btn btn-primary btn-lg w-100">Kirim tautan reset</button>
<p class="text-center mt-3"><a href="{{ route('login') }}">Kembali masuk</a></p>
</form>
@endsection

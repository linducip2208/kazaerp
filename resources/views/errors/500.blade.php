@extends('layouts.auth')
@section('title','Kesalahan Server')
@section('heading','500')
@section('sub','Terjadi kesalahan server. Coba lagi beberapa saat.')
@section('form')
<div class="d-grid gap-2">
<a href="{{ route('dashboard') }}" class="btn btn-primary">Ke dashboard</a>
<a href="{{ route('login') }}" class="btn btn-link">Kembali masuk</a>
</div>
@endsection

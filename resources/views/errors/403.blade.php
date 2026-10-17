@extends('layouts.auth')
@section('title','Akses Ditolak')
@section('heading','403')
@section('sub','Akses ditolak untuk permintaan ini.')
@section('form')
<div class="d-grid gap-2">
<a href="{{ route('dashboard') }}" class="btn btn-primary">Ke dashboard</a>
<a href="{{ route('login') }}" class="btn btn-link">Kembali masuk</a>
</div>
@endsection

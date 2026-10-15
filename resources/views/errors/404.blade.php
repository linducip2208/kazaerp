@extends('layouts.tabler')
@section('title','Halaman Tidak Ditemukan')
@section('content')
<div class="empty-state"><h1>404</h1><p>Halaman Tidak Ditemukan — silakan kembali ke <a href="{{ route('dashboard') }}">dashboard</a>.</p></div>
@endsection

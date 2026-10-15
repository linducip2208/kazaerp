@extends('layouts.tabler')
@section('title','Kesalahan Server')
@section('content')
<div class="empty-state"><h1>500</h1><p>Kesalahan Server — silakan kembali ke <a href="{{ route('dashboard') }}">dashboard</a>.</p></div>
@endsection

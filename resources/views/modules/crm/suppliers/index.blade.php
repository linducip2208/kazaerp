@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="d-flex justify-content-end mb-2 d-print-none"><a href="{{ route('reports.import.suppliers') }}" class="btn btn-sm btn-outline-primary">Impor CSV</a></div>
@include('components.crud-index', ['rows'=>$rows,'meta'=>$meta])
@endsection

@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-index', ['rows'=>$rows,'meta'=>$meta])
@endsection

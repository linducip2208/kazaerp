@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-form', ['row'=>$row,'meta'=>$meta,'method'=>$method,'action'=>$action])
@endsection

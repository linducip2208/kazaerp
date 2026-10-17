@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-show', ['row'=>$row,'meta'=>$meta])
@include('components.possible-duplicates', ['dupes'=>$dupes ?? [], 'meta'=>$meta])
@endsection

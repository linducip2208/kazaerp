@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-show', ['row'=>$row,'meta'=>$meta])
<div class="mt-2 no-print"><a href="{{ route('roles.perms',$row->id) }}" class="btn btn-outline-primary">Kelola Permission</a></div>
@endsection

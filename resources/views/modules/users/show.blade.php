@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-show', ['row'=>$row,'meta'=>$meta])
<div class="mt-2 no-print"><a href="{{ route('users.roles',$row->id) }}" class="btn btn-outline-primary">Kelola Peran & Cakupan</a></div>
@endsection

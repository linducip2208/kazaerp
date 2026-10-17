@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-show', ['row'=>$row,'meta'=>$meta])
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Gabung duplikat (CRM-05)</h3></div>
<div class="card-body">
@if(!empty($row->merged_into_customer_id))
<div class="alert alert-info">Pelanggan ini sudah digabung ke #{{ $row->merged_into_customer_id }}. Histori dokumen tetap pada data ini.</div>
@elseif(!$row->is_active)
<div class="alert alert-warning">Pelanggan nonaktif — tidak bisa digabung.</div>
@else
<form method="POST" action="{{ route('crm.customers.merge', $row->id) }}" class="row g-2">@csrf
<div class="col-md-4"><label class="form-label">Gabung ke pelanggan (induk, satu entitas)</label>
<input type="number" name="target_id" class="form-control" min="1" required placeholder="ID pelanggan induk"></div>
<div class="col-md-12"><button class="btn btn-warning">Gabungkan (sumber dinonaktifkan, histori dipertahankan)</button>
<span class="form-hint">Butuh wewenang <code>crm.convert</code>. Dokumen lama tidak dipindah.</span></div>
</form>
@endif
</div></div>
@endsection

@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-show', ['row'=>$row,'meta'=>$meta])
<div class="card mt-3"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Item Lot + Alokasi Biaya</h3></div>
<div class="col-auto"><form method="POST" action="{{ route('thrift.lots.allocate',$row->id) }}" class="d-inline">@csrf
<select name="method" class="form-select form-select-sm d-inline w-auto"><option value="equal">Rata</option><option value="price">Proporsional harga</option></select>
<button class="btn btn-sm btn-primary">Alokasikan</button></form></div></div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th class="text-end">Harga</th><th class="text-end">Cost Share</th><th>Status</th></tr></thead>
<tbody>@foreach($items as $it)<tr><td>{{ $it->code }}</td><td>{{ $it->name }}</td><td class="text-end">{{ idr($it->price) }}</td><td class="text-end">{{ idr($it->cost_share) }}</td><td>{{ $it->status }}</td></tr>@endforeach</tbody></table></div></div>
@endsection

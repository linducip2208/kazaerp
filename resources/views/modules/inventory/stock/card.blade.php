@extends('layouts.tabler')
@section('title','Kartu Stok')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Kartu Stok — {{ $product->sku }} / {{ $product->name }}</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Gudang</th><th class="text-end">Qty</th><th>Referensi</th><th>Catatan</th></tr></thead>
<tbody>@foreach($rows as $r)<tr><td>{{ tgl_id($r->created_at,true) }}</td><td>{{ $r->warehouse_id }}</td><td class="text-end">{{ $r->qty }}</td><td>{{ $r->ref_type }} #{{ $r->ref_id }}</td><td>{{ $r->note }}</td></tr>@endforeach</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

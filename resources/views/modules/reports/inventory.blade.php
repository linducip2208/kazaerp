@extends('layouts.tabler')
@section('title','Laporan Inventori')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Laporan Inventori</h3></div>
<div class="col-auto"><a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Export CSV</a></div></div></div>
<div class="card-body"><form method="GET" class="row g-2 mb-2"><div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
<div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>SKU</th><th>Produk</th><th class='text-end'>Qty</th><th>Ref</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ $r->created_at }}</td><td>{{ $r->sku }}</td><td>{{ $r->product }}</td><td class='text-end'>{{ $r->qty }}</td><td>{{ $r->ref_type }} #{{ $r->ref_id }}</td></tr>@endforeach</tbody></table></div></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

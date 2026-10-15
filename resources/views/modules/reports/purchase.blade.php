@extends('layouts.tabler')
@section('title','Laporan Pembelian')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Laporan Pembelian</h3></div>
<div class="col-auto"><a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Export CSV</a></div></div></div>
<div class="card-body"><form method="GET" class="row g-2 mb-2"><div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
<div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Supplier</th><th class='text-end'>Total</th><th>Status</th></tr></thead><tbody>
@foreach($rows as $r)<tr> <td>{{ $r->number }}</td><td>{{ $r->date }}</td><td>{{ $r->customer ?? $r->name }}</td><td class='text-end'>{{ idr($r->grand_total) }}</td><td>{{ $r->pay_status ?? $r->status }}</td></tr>@endforeach</tbody></table></div></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

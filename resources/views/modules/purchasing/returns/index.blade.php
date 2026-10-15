@extends('layouts.tabler')
@section('title','Retur Pembelian')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Retur Pembelian</h2></div>
<div class="col-auto"><a href="{{ route('purchasing.returns.create') }}" class="btn btn-primary">+ Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Supplier</th><th class="text-end">Total</th><th>Status</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td>{{ $r->supplier }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td>{{ $r->status }}</td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada retur.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

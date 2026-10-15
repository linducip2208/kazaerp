@extends('layouts.tabler')
@section('title','Invoice Vendor')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Invoice Vendor</h2></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Supplier</th><th class="text-end">Total</th><th>Status</th><th>Match</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ $r->supplier }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td><td>{{ $r->match_status }}</td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada data.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

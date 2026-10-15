@extends('layouts.tabler')
@section('title','Pembayaran Utang')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Pembayaran Utang Vendor</h2></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Supplier</th><th class="text-end">Nominal</th><th>Metode</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td>{{ $r->supplier }}</td><td class="text-end">{{ idr($r->amount) }}</td><td>{{ $r->method }}</td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada pembayaran.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

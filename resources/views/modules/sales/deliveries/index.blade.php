@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">{{ $meta['title'] }}</h2></div>
<div class="col-auto"><a href="{{ route('sales.deliveries.create') }}" class="btn btn-primary">+ Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td><td class="text-end"><a href="{{ route('sales.deliveries.show',$r->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada data.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

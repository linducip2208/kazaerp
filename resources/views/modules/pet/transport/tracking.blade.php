@extends('layouts.tabler')
@section('title','Tracking GPS Transport')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col">
<h2 class="page-title">Tracking GPS Transport</h2>
<div class="text-muted">Posisi terakhir per trip. Driver mengirim posisi melalui API <code>POST /api/v1/trips/{id}/positions</code>.</div>
</div></div></div>
<div class="card mt-2">
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr><th>Order</th><th>Kendaraan</th><th>Driver</th><th>Status Trip</th><th>Posisi terakhir</th><th>Waktu</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr>
<td>{{ $r->order_number }}</td>
<td>{{ $r->plate }}</td>
<td>{{ $r->driver }}</td>
<td><span class="badge bg-secondary-lt">{{ $r->status }}</span></td>
<td>
@if($r->lat !== null)
{{ number_format((float)$r->lat,5) }}, {{ number_format((float)$r->lng,5) }}
@if($r->speed_kmh !== null)<span class="text-muted small">— {{ $r->speed_kmh }} km/j</span>@endif
@else<span class="text-muted">Belum ada titik GPS</span>@endif
</td>
<td>{{ $r->position_at ? tgl_id($r->position_at) : '—' }}</td>
<td class="text-end">
@if($r->lat !== null)<a class="btn btn-sm btn-outline-primary" target="_blank" href="https://www.openstreetmap.org/?mlat={{ $r->lat }}&mlon={{ $r->lng }}#map=16/{{ $r->lat }}/{{ $r->lng }}">Peta</a>@endif
<a class="btn btn-sm btn-outline-secondary" href="{{ route('pet.transport.track',$r->transport_order_id) }}">Detail</a>
</td>
</tr>@empty<tr><td colspan="7"><div class="empty-state">Belum ada trip. Buat transport order + trip terlebih dahulu.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

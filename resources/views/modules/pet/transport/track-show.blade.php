@extends('layouts.tabler')
@section('title','Track '.$order->number)
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col">
<h2 class="page-title">Transport {{ $order->number }}</h2>
<div class="text-muted">{{ $order->pickup_address }} → {{ $order->delivery_address }}</div>
</div><div class="col-auto"><a href="{{ route('pet.transport.tracking') }}" class="btn btn-link">← Semua trip</a></div></div></div>

<div class="row row-cards mt-2">
@forelse($trips as $t)
<div class="col-md-6"><div class="card"><div class="card-header">
<h3 class="card-title">{{ $t->plate }} — {{ $t->driver }}</h3>
<span class="badge bg-secondary-lt">{{ $t->status }}</span></div>
<div class="card-body">
<div>Berangkat: {{ $t->depart_at ? tgl_id($t->depart_at) : '—' }} · Tiba: {{ $t->arrive_at ? tgl_id($t->arrive_at) : '—' }}</div>
@if($t->lat !== null)
<div class="mt-2">Posisi terakhir: <strong>{{ number_format((float)$t->lat,5) }}, {{ number_format((float)$t->lng,5) }}</strong>
({{ $t->position_at ? tgl_id($t->position_at) : '—' }})</div>
<a class="btn btn-sm btn-outline-primary mt-2" target="_blank" href="https://www.openstreetmap.org/?mlat={{ $t->lat }}&mlon={{ $t->lng }}#map=16/{{ $t->lat }}/{{ $t->lng }}">Buka di OpenStreetMap</a>
@else<div class="text-muted mt-2">Belum ada titik GPS untuk trip ini.</div>@endif
</div></div></div>
@empty
<div class="col-12"><div class="card"><div class="card-body"><div class="empty-state">Transport order ini belum memiliki trip.</div></div></div></div>
@endforelse
</div>

<div class="card mt-2"><div class="card-header"><h3 class="card-title">Riwayat Posisi (50 terbaru)</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Lat</th><th>Lng</th><th>Kecepatan</th><th></th></tr></thead>
<tbody>@forelse($positions as $p)<tr>
<td>{{ tgl_id($p->recorded_at) }}</td>
<td>{{ number_format((float)$p->lat,5) }}</td>
<td>{{ number_format((float)$p->lng,5) }}</td>
<td>{{ $p->speed_kmh !== null ? $p->speed_kmh.' km/j' : '—' }}</td>
<td class="text-end"><a class="btn btn-sm btn-outline-primary" target="_blank" href="https://www.openstreetmap.org/?mlat={{ $p->lat }}&mlon={{ $p->lng }}#map=16/{{ $p->lat }}/{{ $p->lng }}">Peta</a></td>
</tr>@empty<tr><td colspan="5" class="text-muted">Belum ada posisi.</td></tr>@endforelse</tbody></table></div></div>
@endsection

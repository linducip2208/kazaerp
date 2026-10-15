@extends('layouts.tabler')
@section('title','Worksheet Intercompany')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Worksheet Eliminasi (dasar manual — otomatisasi butuh kebijakan Finance)</h3></div>
<div class="card-body">
<form method="GET" class="row g-2 mb-3"><div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
<div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form>
<h4>Saldo per pasangan entitas (untuk jurnal eliminasi manual)</h4>
<div class="table-responsive"><table class="table"><thead><tr><th>Dari</th><th>Ke</th><th class="text-end">Total</th><th class="text-end">Transaksi</th></tr></thead>
<tbody>@forelse($pairs as $p)<tr><td>{{ $p->dari }}</td><td>{{ $p->ke }}</td><td class="text-end">{{ idr($p->total) }}</td><td class="text-end">{{ $p->n }}</td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada transaksi intercompany.</div></td></tr>@endforelse</tbody></table></div>
<h4 class="mt-3">Register transaksi</h4>
<div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Dari → Ke</th><th class="text-end">Nominal</th><th>Status</th></tr></thead>
<tbody>@foreach($txns as $t)<tr><td>{{ $t->number }}</td><td>{{ tgl_id($t->date) }}</td><td>{{ $t->from_code }} → {{ $t->to_code }}</td><td class="text-end">{{ idr($t->amount) }}</td><td>{{ $t->status }}</td></tr>@endforeach</tbody></table></div>
</div>
<div class="card-footer">{{ $txns->links() }}</div></div>
@endsection

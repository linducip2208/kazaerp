@extends('layouts.tabler')
@section('title','Budget vs Aktual')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Budget vs Aktual (mutasi jurnal tahun anggaran)</h3></div>
<div class="col-auto"><form method="GET" class="d-flex gap-1"><select name="budget_id" class="form-select" onchange="this.form.submit()">@foreach($budgets as $b)<option value="{{ $b->id }}" @selected($bid==$b->id)>{{ $b->name }}</option>@endforeach</select></form></div></div></div>
<div class="card-body"><div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Akun</th><th class="text-end">Budget</th><th class="text-end">Aktual (net)</th><th class="text-end">Sisa</th><th class="text-end">Serapan</th></tr></thead>
<tbody>@forelse($lines as $l)@php $pct = $l->budget > 0 ? abs($l->actual_net)/$l->budget*100 : 0; @endphp<tr><td>{{ $l->code }}</td><td>{{ $l->name }}</td><td class="text-end">{{ idr($l->budget) }}</td><td class="text-end">{{ idr($l->actual_net) }}</td><td class="text-end">{{ idr($l->budget - abs($l->actual_net)) }}</td><td class="text-end"><span class="badge {{ $pct>100?'bg-red':($pct>80?'bg-yellow':'bg-green') }}">{{ number_format($pct,1) }}%</span></td></tr>
@empty<tr><td colspan="6"><div class="empty-state">Belum ada budget. Buat via database/konfigurasi atau modul budget.</div></td></tr>@endforelse</tbody></table></div></div></div>
@endsection

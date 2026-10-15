@extends('layouts.tabler')
@section('title','Work Order '.$wo->number)
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col">
<h2 class="page-title">Work Order {{ $wo->number }}</h2>
<div class="text-muted">{{ $wo->product }} ({{ $wo->sku }}) — BOM v{{ $wo->bom_version_locked }} — {{ tgl_id($wo->date) }}</div>
</div>
<div class="col-auto"><span class="badge {{ ['released'=>'bg-blue-lt','in_progress'=>'bg-yellow-lt','completed'=>'bg-green-lt'][$wo->status] ?? 'bg-secondary-lt' }} p-2">{{ $wo->status }}</span></div></div></div>

<div class="row row-cards mt-2">
<div class="col-md-8"><div class="card"><div class="card-header"><h3 class="card-title">Material (BOM snapshot)</h3></div>
<div class="table-responsive"><table class="table">
<thead><tr><th>Material</th><th class="text-end">Butuh</th><th class="text-end">Issued</th><th class="text-end">Std Cost</th><th class="text-end">Actual Cost</th><th class="text-end">Nilai Issued</th></tr></thead>
<tbody>@foreach($lines as $l)<tr>
<td><div>{{ $l->material }}</div><div class="text-muted small">{{ $l->sku }}</div></td>
<td class="text-end">{{ rtrim(rtrim(number_format((float)$l->qty_required,4,',','.'),'0'),',') }}</td>
<td class="text-end">{{ rtrim(rtrim(number_format((float)$l->qty_issued,4,',','.'),'0'),',') }}</td>
<td class="text-end">{{ idr($l->unit_cost) }}</td>
<td class="text-end">{{ idr($l->issued_unit_cost) }}</td>
<td class="text-end">{{ idr((float)$l->qty_issued*(float)$l->issued_unit_cost) }}</td>
</tr>@endforeach</tbody></table></div></div></div>

<div class="col-md-4">
<div class="card"><div class="card-header"><h3 class="card-title">Ringkasan Biaya</h3></div><div class="card-body">
<dl class="row mb-0">
<dt class="col-7">Biaya standar</dt><dd class="col-5 text-end">{{ idr($wo->standard_cost) }}</dd>
<dt class="col-7">Biaya aktual</dt><dd class="col-5 text-end">{{ idr($wo->actual_cost) }}</dd>
<dt class="col-7">Variance</dt><dd class="col-5 text-end {{ (float)$wo->variance_amount < 0 ? 'text-danger' : 'text-success' }}">{{ idr($wo->variance_amount) }}</dd>
<dt class="col-7">Qty jadi / reject</dt><dd class="col-5 text-end">{{ (float)$wo->qty_good }} / {{ (float)$wo->qty_reject }}</dd>
<dt class="col-7">Mulai / selesai</dt><dd class="col-5 text-end">{{ $wo->started_at ? tgl_id($wo->started_at) : '—' }}<br>{{ $wo->completed_at ? tgl_id($wo->completed_at) : '—' }}</dd>
</dl></div></div>

@if(in_array($wo->status,['released','in_progress']) && auth()->user()->hasPermission('mfg.execute'))
<div class="card mt-2"><div class="card-header"><h3 class="card-title">Aksi Produksi</h3></div><div class="card-body">
<form method="POST" action="{{ route('mfg.workorders.issue',$wo->id) }}">@csrf
<button class="btn btn-outline-primary w-100">1. Issue Material (sisa)</button>
<div class="form-hint">Semua sisa material dikeluarkan atomik. Gagal bila stok kurang.</div>
</form>
@if($wo->status==='in_progress')
<form method="POST" action="{{ route('mfg.workorders.produce',$wo->id) }}" class="mt-3">@csrf
<div class="row g-2">
<div class="col-6"><label class="form-label">Qty Bagus</label><input type="number" step="any" min="0.0001" name="qty_good" class="form-control" required></div>
<div class="col-6"><label class="form-label">Qty Reject</label><input type="number" step="any" min="0" name="qty_reject" class="form-control" value="0"></div>
<div class="col-12"><label class="form-label">Catatan</label><input name="note" class="form-control" placeholder="opsional"></div>
</div>
<button class="btn btn-success w-100 mt-2">2. Produce (output + variance)</button>
</form>
@endif
</div></div>
@endif
</div>
</div>

<div class="card mt-2"><div class="card-header"><h3 class="card-title">Riwayat Status</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Dari</th><th>Ke</th><th>Catatan</th></tr></thead>
<tbody>@forelse($history as $h)<tr><td>{{ tgl_id($h->created_at) }}</td><td>{{ $h->from_status ?? '—' }}</td><td>{{ $h->to_status }}</td><td>{{ $h->note ?? '—' }}</td></tr>
@empty<tr><td colspan="4" class="text-muted">Belum ada riwayat.</td></tr>@endforelse</tbody></table></div></div>
@endsection

@extends('layouts.tabler')
@section('title','Dashboard')
@section('content')
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">Dashboard</h2><div class="text-muted">Periode berjalan • {{ tgl_id(now(), true) }} WIB</div></div>
</div></div>
<div class="row row-cards mt-2">
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Penjualan Bulan Ini</div><div class="h1">{{ idr($salesMonth) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Piutang Beredar</div><div class="h1">{{ idr($receivable) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Utang Beredar</div><div class="h1">{{ idr($payable) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Stok Menipis / Approval Pending</div><div class="h1">{{ $lowStock }} / {{ $pendingApprovals }}</div></div></div></div>
</div>
@if($perEntity->count())
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Pendapatan per Badan Usaha (bulan ini)</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Badan Usaha</th><th class="text-end">Total</th></tr></thead>
<tbody>@foreach($perEntity as $e)<tr><td>{{ $e->code }}</td><td>{{ $e->name }}</td><td class="text-end">{{ idr($e->total) }}</td></tr>@endforeach</tbody></table></div></div>
@endif
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Tren Pendapatan (6 bulan)</h3></div>
<div class="card-body"><canvas id="trendChart" height="90"></canvas></div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('trendChart');
  if (el && window.KazaChart) {
    new window.KazaChart(el, { type: 'bar',
      data: { labels: @json($trend['labels']), datasets: [{ label: 'Pendapatan (Rp)', data: @json($trend['values']), backgroundColor: '#206bc4' }] },
      options: { responsive: true, plugins: { legend: { display: false } } } });
  }
});
</script>
@if($fin)
<div class="row row-cards mt-3">
<div class="col-12"><h3>💰 Keuangan</h3></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Posisi Kas</div><div class="h1">{{ idr($fin['cash']) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Dokumen Belum Posting</div><div class="h1">{{ $fin['unposted'] }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Piutang Overdue</div><div class="h1">{{ idr($fin['overdue_ar']) }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Periode Aktif</div><div class="h1">{{ $fin['period']->name ?? '-' }} {{ ($fin['period']->is_closed ?? false) ? '(Tutup)' : '' }}</div></div></div></div>
</div>
@endif
@if($ops)
<div class="row row-cards mt-3">
<div class="col-12"><h3>🏭 Operasional</h3></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">SO Terbuka</div><div class="h1">{{ $ops['open_so'] }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Antrean Grooming</div><div class="h1">{{ $ops['groom_queue'] }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Boarding Menginap</div><div class="h1">{{ $ops['boarding_in'] }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">PO Berjalan</div><div class="h1">{{ $ops['pending_po'] }}</div></div></div></div>
</div>
@endif
@if($hr)
<div class="row row-cards mt-3">
<div class="col-12"><h3>🧑‍💼 HR</h3></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Karyawan Aktif</div><div class="h1">{{ $hr['active_emp'] }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Hadir Hari Ini</div><div class="h1">{{ $hr['present_today'] }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Cuti Pending</div><div class="h1">{{ $hr['pending_leave'] }}</div></div></div></div>
<div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="text-muted">Payroll Draft</div><div class="h1">{{ $hr['payroll_draft'] }}</div></div></div></div>
</div>
@endif
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Sales Order Terbaru</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th></tr></thead>
<tbody>@forelse($orders as $o)<tr><td><a href="{{ route('sales.orders.show',$o->id) }}">{{ $o->number }}</a></td><td>{{ tgl_id($o->date) }}</td><td class="text-end">{{ idr($o->grand_total) }}</td><td><span class="badge bg-blue">{{ $o->status }}</span></td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada order. <a href="{{ route('sales.orders.create') }}">Buat pertama</a>.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection

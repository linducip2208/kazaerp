@extends('layouts.tabler')
@section('title','Dashboard')
@section('content')
@php $me = auth()->user(); @endphp
<div class="dash-head">
  <div>
    <h2 class="dash-title">Dashboard</h2>
    <div class="dash-sub">Periode berjalan @if($entityCode)· {{ $entityCode }}@endif · {{ tgl_id(now(), true) }} WIB</div>
  </div>
  <div class="d-flex gap-2">
    @if($me->hasPermission('reports.view'))
    <a href="{{ route('reports.sales') }}" class="btn btn-outline-primary">Laporan Penjualan</a>
    @endif
    @if($me->hasPermission('sales.view'))
    <a href="{{ route('sales.orders.index') }}" class="btn btn-primary">Sales Order</a>
    @endif
  </div>
</div>

@if(($me->hasPermission('governance.view') && $pendingApprovals > 0) || ($me->hasPermission('inventory.view') && $lowStock > 0))
<div class="row row-cards g-3 mb-3">
  @if($me->hasPermission('governance.view') && $pendingApprovals > 0)
  <div class="col-md-6">
    <a class="priority-banner warn" href="{{ route('gov.approvals.index') }}">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l8 3v6c0 4.5-3.2 7.6-8 9-4.8-1.4-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
      <span class="grow"><strong>{{ $pendingApprovals }} dokumen menunggu persetujuan.</strong><br><span>Segera putuskan agar transaksi tidak tertahan.</span></span>
      <span aria-hidden="true">→</span>
    </a>
  </div>
  @endif
  @if($me->hasPermission('inventory.view') && $lowStock > 0)
  <div class="col-md-6">
    <a class="priority-banner info" href="{{ route('inventory.stock.index') }}">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
      <span class="grow"><strong>{{ $lowStock }} item stok menipis.</strong><br><span>Periksa kartu stok dan rencanakan pengadaan.</span></span>
      <span aria-hidden="true">→</span>
    </a>
  </div>
  @endif
</div>
@endif

<div class="row row-cards g-3">
  <div class="col-sm-6 col-lg-3">
    <div class="card kpi-card"><div class="card-body">
      <div class="kpi-top"><span class="kpi-ico blue"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg></span><span class="kpi-label">Pendapatan bulan ini</span></div>
      <div class="kpi-value">{{ idr($salesMonth) }}</div>
      <div class="kpi-foot text-muted">@if($me->hasPermission('sales.view'))<a href="{{ route('sales.invoices.index') }}">Lihat invoice →</a>@else &nbsp;@endif</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card kpi-card"><div class="card-body">
      <div class="kpi-top"><span class="kpi-ico amber"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg></span><span class="kpi-label">Piutang beredar</span></div>
      <div class="kpi-value">{{ idr($receivable) }}</div>
      <div class="kpi-foot text-muted">@if($me->hasPermission('finance.view'))<a href="{{ route('finance.aging') }}">Lihat aging AR/AP →</a>@else &nbsp;@endif</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card kpi-card"><div class="card-body">
      <div class="kpi-top"><span class="kpi-ico red"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg></span><span class="kpi-label">Utang beredar</span></div>
      <div class="kpi-value">{{ idr($payable) }}</div>
      <div class="kpi-foot text-muted">@if($me->hasPermission('finance.view'))<a href="{{ route('finance.aging') }}">Lihat aging AR/AP →</a>@else &nbsp;@endif</div>
    </div></div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card kpi-card"><div class="card-body">
      <div class="kpi-top"><span class="kpi-ico slate"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l8 3v6c0 4.5-3.2 7.6-8 9-4.8-1.4-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span><span class="kpi-label">Stok menipis / Approval pending</span></div>
      <div class="kpi-value">{{ $lowStock }} / {{ $pendingApprovals }}</div>
      <div class="kpi-foot text-muted">
        @if($me->hasPermission('inventory.view'))<a href="{{ route('inventory.stock.index') }}">Stok →</a>@endif
        @if($me->hasPermission('inventory.view') && $me->hasPermission('governance.view')) · @endif
        @if($me->hasPermission('governance.view'))<a href="{{ route('gov.approvals.index') }}">Approval →</a>@endif
        @if(!$me->hasPermission('inventory.view') && !$me->hasPermission('governance.view')) &nbsp;@endif
      </div>
    </div></div>
  </div>
</div>

<div class="row row-cards g-3 mt-1">
  <div class="col-lg-8">
    <div class="card"><div class="card-header"><div>
      <h3 class="card-title mb-0">Tren pendapatan</h3>
      <div class="text-muted" style="font-size:.8rem">6 bulan terakhir · nominal tertagih (invoice)</div>
    </div></div>
      <div class="card-body">
        <div class="chart-wrap">
          <canvas id="trendChart" role="img" aria-label="Grafik tren pendapatan 6 bulan"></canvas>
          @if(!collect($trend['values'] ?? [])->sum())<div class="chart-empty">Belum ada pendapatan pada 6 bulan terakhir.</div>@endif
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100"><div class="card-header"><h3 class="card-title mb-0">Perlu perhatian</h3></div>
      <div class="card-body">
        @if($me->hasPermission('governance.view'))
        <div class="text-muted mb-1" style="font-size:.78rem;font-weight:600">PERSETUJUAN PENDING</div>
        @if($pendingList->count())
        <ul class="mini-list mb-3">
          @foreach($pendingList as $p)
          <li><span class="grow">{{ $p->doc_type }} #{{ $p->doc_id }}</span><span class="text-muted num">{{ tgl_id($p->created_at) }}</span></li>
          @endforeach
        </ul>
        <div class="mb-3"><a href="{{ route('gov.approvals.index') }}" class="btn btn-sm btn-outline-primary w-100">Putuskan ({{ $pendingApprovals }})</a></div>
        @else
        <p class="text-muted mb-3" style="font-size:.86rem">Tidak ada dokumen menunggu persetujuan.</p>
        @endif
        @endif
        @if($me->hasPermission('inventory.view'))
        <div class="text-muted mb-1" style="font-size:.78rem;font-weight:600">STOK MENIPIS</div>
        @if($lowItems->count())
        <ul class="mini-list">
          @foreach($lowItems as $li)
          <li><span class="grow">{{ $li->sku }} · {{ Str::limit($li->name, 28) }}</span><span class="badge bg-yellow-lt num">{{ rtrim(rtrim(number_format((float) $li->avail, 2, ',', '.'), '0'), ',') }} {{ $li->wh }}</span></li>
          @endforeach
        </ul>
        <div class="mt-2"><a href="{{ route('inventory.stock.index') }}" class="btn btn-sm btn-outline-primary w-100">Lihat stok ({{ $lowStock }})</a></div>
        @else
        <p class="text-muted mb-0" style="font-size:.86rem">Tidak ada item di bawah ambang.</p>
        @endif
        @endif
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('trendChart');
  if (!el || !window.KazaChart) return;
  const labels = @json($trend['labels']);
  const values = @json($trend['values']);
  const fmtRp = (v) => 'Rp ' + Number(v || 0).toLocaleString('id-ID');
  const ctx = el.getContext('2d');
  const grad = ctx.createLinearGradient(0, 0, 0, 280);
  grad.addColorStop(0, 'rgba(32,107,196,.88)');
  grad.addColorStop(1, 'rgba(32,107,196,.12)');
  new window.KazaChart(el, { type: 'bar',
    data: { labels, datasets: [{ label: 'Pendapatan', data: values, backgroundColor: grad,
      borderColor: '#206bc4', borderWidth: 1, borderRadius: 6, maxBarThickness: 44 }] },
    options: { responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false },
        tooltip: { backgroundColor: '#1d2733', padding: 10, displayColors: false,
          callbacks: { label: (c) => ' ' + fmtRp(c.parsed.y) } } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#667382', font: { size: 11 } } },
        y: { beginAtZero: true, grid: { color: '#edf0f5' }, border: { display: false },
          ticks: { color: '#667382', font: { size: 11 }, maxTicksLimit: 6,
            callback: (v) => v >= 1000000 ? 'Rp ' + (v/1000000).toLocaleString('id-ID',{maximumFractionDigits:1}) + ' jt' : fmtRp(v) } } } } });
});
</script>

<div class="row row-cards g-3 mt-1">
  @if($perEntity->count())
  <div class="col-lg-5">
    <div class="card h-100"><div class="card-header"><div>
      <h3 class="card-title mb-0">Pendapatan per badan usaha</h3>
      <div class="text-muted" style="font-size:.8rem">Bulan berjalan · nominal invoice, bukan laba</div>
    </div></div>
      <div class="table-responsive"><table class="table table-clean card-table">
        <thead><tr><th>Kode</th><th>Badan usaha</th><th class="text-end">Total</th></tr></thead>
        <tbody>@foreach($perEntity as $e)<tr><td class="num">{{ $e->code }}</td><td>{{ $e->name }}</td><td class="text-end num">{{ idr($e->total) }}</td></tr>@endforeach</tbody>
      </table></div>
    </div>
  </div>
  @endif
  <div class="{{ $perEntity->count() ? 'col-lg-7' : 'col-12' }}">
    <div class="card h-100"><div class="card-header"><div class="row align-items-center w-100">
      <div class="col"><h3 class="card-title mb-0">Sales order terbaru</h3></div>
      @if($me->hasPermission('sales.view'))
      <div class="col-auto"><a href="{{ route('sales.orders.index') }}" class="btn btn-sm btn-outline-primary">Semua order</a></div>
      @endif
    </div></div>
      <div class="table-responsive"><table class="table table-clean card-table">
        <thead><tr><th>Nomor</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th></tr></thead>
        <tbody>@forelse($orders as $o)<tr>
          <td>@if($me->hasPermission('sales.view'))<a href="{{ route('sales.orders.show',$o->id) }}">{{ $o->number }}</a>@else {{ $o->number }}@endif</td>
          <td class="num">{{ tgl_id($o->date) }}</td><td class="text-end num">{{ idr($o->grand_total) }}</td>
          <td><span class="badge bg-blue-lt">{{ $o->status }}</span></td></tr>
        @empty<tr><td colspan="4"><div class="empty">Belum ada order. @if($me->hasPermission('sales.view'))<a href="{{ route('sales.orders.create') }}">Buat pertama</a>.@endif</div></td></tr>@endforelse</tbody>
      </table></div>
    </div>
  </div>
</div>

@if($fin)
<div class="card mt-3"><div class="card-header"><div class="row align-items-center w-100">
  <div class="col"><h3 class="section-title">Keuangan</h3><div class="text-muted" style="font-size:.8rem">Kas, penagihan &amp; periode</div></div>
  <div class="col-auto"><a href="{{ route('finance.journals.index') }}" class="btn btn-sm btn-outline-primary">Buka keuangan</a></div>
</div></div>
  <div class="card-body"><div class="row g-2">
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Posisi kas</div><div class="t-value">{{ idr($fin['cash']) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Dokumen belum posting</div><div class="t-value">{{ $fin['unposted'] }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Piutang overdue</div><div class="t-value">{{ idr($fin['overdue_ar']) }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Periode aktif</div><div class="t-value" style="font-size:.95rem">{{ $fin['period'] ? $fin['period']->name : '—' }}@if($fin['period'] && $fin['period']->is_closed) <span class="badge bg-red-lt">Tutup</span>@endif</div></div></div>
  </div></div>
</div>
@endif
@if($ops)
<div class="card mt-3"><div class="card-header"><div class="row align-items-center w-100">
  <div class="col"><h3 class="section-title">Operasional</h3><div class="text-muted" style="font-size:.8rem">Penjualan, pet care &amp; pembelian berjalan</div></div>
  @if($me->hasPermission('inventory.view'))<div class="col-auto"><a href="{{ route('inventory.stock.index') }}" class="btn btn-sm btn-outline-primary">Buka inventori</a></div>@endif
</div></div>
  <div class="card-body"><div class="row g-2">
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">SO terbuka</div><div class="t-value">{{ $ops['open_so'] }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Antrean grooming</div><div class="t-value">{{ $ops['groom_queue'] }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Boarding menginap</div><div class="t-value">{{ $ops['boarding_in'] }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">PO berjalan</div><div class="t-value">{{ $ops['pending_po'] }}</div></div></div>
  </div></div>
</div>
@endif
@if($hr)
<div class="card mt-3"><div class="card-header"><div class="row align-items-center w-100">
  <div class="col"><h3 class="section-title">SDM</h3><div class="text-muted" style="font-size:.8rem">Kehadiran, cuti &amp; payroll</div></div>
  <div class="col-auto"><a href="{{ route('hr.employees.index') }}" class="btn btn-sm btn-outline-primary">Buka HR</a></div>
</div></div>
  <div class="card-body"><div class="row g-2">
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Karyawan aktif</div><div class="t-value">{{ $hr['active_emp'] }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Hadir hari ini</div><div class="t-value">{{ $hr['present_today'] }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Cuti pending</div><div class="t-value">{{ $hr['pending_leave'] }}</div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-tile"><div class="t-label">Payroll draft</div><div class="t-value">{{ $hr['payroll_draft'] }}</div></div></div>
  </div></div>
</div>
@endif
@endsection

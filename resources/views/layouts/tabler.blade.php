<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title','Dashboard') — KazaERP</title>
@vite(['resources/css/app.css','resources/js/app.js'])
<link rel="manifest" href="/manifest.webmanifest">
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten utama</a>
@php
$u = auth()->user();
$ctx = session('erp_context', []);
$pend = DB::table('approvals')->where('status','pending')->count();
$notif = DB::table('notifications')->where('user_id', auth()->id())->whereNull('read_at')->orderByDesc('id')->limit(5)->get();
$notifCount = DB::table('notifications')->where('user_id', auth()->id())->whereNull('read_at')->count();
$ents = $u->hasRole('super_admin','holding_owner','executive') ? DB::table('legal_entities')->orderBy('code')->get() : DB::table('legal_entities')->where('id',$u->legal_entity_id)->get();
$brs = DB::table('branches')->when(!($u->hasRole('super_admin','holding_owner','executive')), fn($q)=>$q->where('id',$u->branch_id))->orderBy('code')->get();
$activeEid = $ctx['legal_entity_id'] ?? $u->legal_entity_id;
$activeBid = $ctx['branch_id'] ?? $u->branch_id;
$activeEnt = $ents->firstWhere('id', $activeEid);
$activeBr = $brs->firstWhere('id', $activeBid);
$isActive = fn($p) => request()->routeIs($p) ? 'active' : '';
@endphp
<div class="admin-shell">
  <div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
  <aside class="sidebar no-print" id="sidebar" aria-label="Navigasi utama">
    <div class="sidebar-brand">
      <a href="{{ route('dashboard') }}" class="brand-link" aria-label="KazaERP Dashboard">
        <span class="brand-mark" aria-hidden="true">K</span>
        <span class="brand-text">KazaERP <span class="badge bg-blue-lt">Kazaka Group</span></span>
      </a>
      <button class="btn btn-icon btn-sm sidebar-close d-lg-none" id="sidebarClose" aria-label="Tutup navigasi" aria-controls="sidebar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
      </button>
    </div>
    <nav class="sidebar-nav">
      <div class="side-section"><div class="side-label">Utama</div>
        <a class="side-link {{ $isActive('dashboard') }}" href="{{ route('dashboard') }}">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
          <span>Dashboard</span></a>
      </div>
      @if($u->hasPermission('org.view') || $u->hasPermission('users.view'))
      <div class="side-section"><div class="side-label">Organisasi &amp; Akses</div>
        @if($u->hasPermission('org.view'))
        <details class="side-group" @if(request()->routeIs('org.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-4h6v4M9 11h.01M15 11h.01"/></svg>
            <span>Organisasi</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('org.holdings.*') }}" href="{{ route('org.holdings.index') }}">Holding</a>
            <a class="{{ $isActive('org.entities.*') }}" href="{{ route('org.entities.index') }}">Badan Usaha</a>
            <a class="{{ $isActive('org.branches.*') }}" href="{{ route('org.branches.index') }}">Cabang</a>
            <a class="{{ $isActive('org.warehouses.*') }}" href="{{ route('org.warehouses.index') }}">Gudang</a>
          </div>
        </details>
        @endif
        @if($u->hasPermission('users.view'))
        <details class="side-group" @if(request()->routeIs('users.*','roles.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20v-1a6.5 6.5 0 0 1 6.5-6.5h0A6.5 6.5 0 0 1 15.5 19v1"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18.5 13a6.5 6.5 0 0 1 3 5v1"/></svg>
            <span>Akses</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('users.*') }}" href="{{ route('users.index') }}">Pengguna</a>
            <a class="{{ $isActive('roles.*') }}" href="{{ route('roles.index') }}">Peran</a>
          </div>
        </details>
        @endif
      </div>
      @endif
      <div class="side-section"><div class="side-label">Operasional</div>
        @if($u->hasPermission('crm.view'))
        <details class="side-group" @if(request()->routeIs('crm.*','master.*','files.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="11" r="2"/><path d="M5.5 16c.6-1.5 1.7-2.3 3-2.3s2.4.8 3 2.3M14 9.5h5M14 13h5"/></svg>
            <span>CRM &amp; Master</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('crm.customers.*') }}" href="{{ route('crm.customers.index') }}">Pelanggan</a>
            <a class="{{ $isActive('crm.suppliers.*') }}" href="{{ route('crm.suppliers.index') }}">Supplier</a>
            <a class="{{ $isActive('crm.leads.*') }}" href="{{ route('crm.leads.index') }}">Lead</a>
            <a class="{{ $isActive('crm.opportunities.*') }}" href="{{ route('crm.opportunities.index') }}">Opportunity</a>
            <a class="{{ $isActive('crm.followups.*') }}" href="{{ route('crm.followups.index') }}">Follow-up</a>
            <a class="{{ $isActive('crm.complaints.*') }}" href="{{ route('crm.complaints.index') }}">Komplain</a>
            <a class="{{ $isActive('master.*') }}" href="{{ route('master.products.index') }}">Produk</a>
            <a class="{{ $isActive('files.*') }}" href="{{ route('files.index') }}">Berkas</a>
          </div>
        </details>
        @endif
        @if($u->hasPermission('sales.view'))
        <details class="side-group" @if(request()->routeIs('sales.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/></svg>
            <span>Penjualan</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('sales.quotes.*') }}" href="{{ route('sales.quotes.index') }}">Quotation</a>
            <a class="{{ $isActive('sales.orders.*') }}" href="{{ route('sales.orders.index') }}">Sales Order</a>
            <a class="{{ $isActive('sales.deliveries.*') }}" href="{{ route('sales.deliveries.index') }}">Delivery</a>
            <a class="{{ $isActive('sales.invoices.*') }}" href="{{ route('sales.invoices.index') }}">Invoice</a>
            <a class="{{ $isActive('sales.creditnotes.*') }}" href="{{ route('sales.creditnotes.index') }}">Credit Note</a>
            <a class="{{ $isActive('sales.returns.*') }}" href="{{ route('sales.returns.index') }}">Retur</a>
            @if($u->hasPermission('pos.operate'))<a class="{{ $isActive('sales.pos.*') }}" href="{{ route('sales.pos.index') }}">POS Kasir</a>@endif
          </div>
        </details>
        @endif
        @if($u->hasPermission('inventory.view'))
        <details class="side-group" @if(request()->routeIs('inventory.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/></svg>
            <span>Inventori</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('inventory.stock.*') }}" href="{{ route('inventory.stock.index') }}">Stok &amp; Kartu Stok</a>
            <a class="{{ $isActive('inventory.receipts.*') }}" href="{{ route('inventory.receipts.index') }}">Penerimaan</a>
            <a class="{{ $isActive('inventory.batches.*') }}" href="{{ route('inventory.batches.index') }}">Batch &amp; QC</a>
          </div>
        </details>
        @endif
        @if($u->hasPermission('purchasing.view'))
        <details class="side-group" @if(request()->routeIs('purchasing.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="20" r="1.5"/><circle cx="17" cy="20" r="1.5"/><path d="M3 4h2l2.5 11h10L20 8H7"/></svg>
            <span>Pembelian</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('purchasing.requisitions.*') }}" href="{{ route('purchasing.requisitions.index') }}">Permintaan</a>
            <a class="{{ $isActive('purchasing.orders.*') }}" href="{{ route('purchasing.orders.index') }}">Purchase Order</a>
            <a class="{{ $isActive('purchasing.vinvoices.*') }}" href="{{ route('purchasing.vinvoices.index') }}">Invoice &amp; Bayar Vendor</a>
          </div>
        </details>
        @endif
        @if($u->hasPermission('pet.view'))
        <details class="side-group" @if(request()->routeIs('pet.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="15.5" r="4"/><circle cx="6.5" cy="10" r="1.6"/><circle cx="12" cy="7.5" r="1.6"/><circle cx="17.5" cy="10" r="1.6"/></svg>
            <span>Pet Care</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('pet.owners.*') }}" href="{{ route('pet.owners.index') }}">Pemilik</a>
            <a class="{{ $isActive('pet.pets.*') }}" href="{{ route('pet.pets.index') }}">Hewan</a>
            <a class="{{ $isActive('pet.vet.*') }}" href="{{ route('pet.vet.index') }}">Veteriner</a>
            <a class="{{ $isActive('pet.grooming.*') }}" href="{{ route('pet.grooming.index') }}">Grooming</a>
            <a class="{{ $isActive('pet.boarding.*') }}" href="{{ route('pet.boarding.index') }}">Pet Hotel</a>
            <a class="{{ $isActive('pet.transport.*') }}" href="{{ route('pet.transport.index') }}">Transport</a>
            @if($u->hasPermission('pet.transport'))<a class="{{ $isActive('pet.transport.tracking') }}" href="{{ route('pet.transport.tracking') }}">Tracking GPS</a>@endif
          </div>
        </details>
        @endif
        @if($u->hasPermission('mfg.view')||$u->hasPermission('thrift.view')||$u->hasPermission('studio.view')||$u->hasPermission('rental.view'))
        <details class="side-group" @if(request()->routeIs('mfg.*','thrift.*','studio.*','rental.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 21V10l6 4v-4l6 4V6h6v15zM3 21h18"/></svg>
            <span>Unit Bisnis</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            @if($u->hasPermission('mfg.view'))<a class="{{ $isActive('mfg.designs.*') }}" href="{{ route('mfg.designs.index') }}">Desain Apparel</a>@endif
            @if($u->hasPermission('mfg.view'))<a class="{{ $isActive('mfg.boms.*') }}" href="{{ route('mfg.boms.index') }}">BOM</a>@endif
            @if($u->hasPermission('mfg.plan'))<a class="{{ $isActive('mfg.workorders.*') }}" href="{{ route('mfg.workorders.index') }}">Work Order</a>@endif
            @if($u->hasPermission('thrift.view'))<a class="{{ $isActive('thrift.lots.*') }}" href="{{ route('thrift.lots.index') }}">Lot Thrift</a>@endif
            @if($u->hasPermission('thrift.view'))<a class="{{ $isActive('thrift.items.*') }}" href="{{ route('thrift.items.index') }}">Item Thrift</a>@endif
            @if($u->hasPermission('studio.view'))<a class="{{ $isActive('studio.projects.*') }}" href="{{ route('studio.projects.index') }}">Studio</a>@endif
            @if($u->hasPermission('rental.view'))<a class="{{ $isActive('rental.catalogs.*') }}" href="{{ route('rental.catalogs.index') }}">Katalog Sewa</a>@endif
            @if($u->hasPermission('rental.view'))<a class="{{ $isActive('rental.reservations.*') }}" href="{{ route('rental.reservations.index') }}">Reservasi Sewa</a>@endif
          </div>
        </details>
        @endif
      </div>
      <div class="side-section"><div class="side-label">Keuangan &amp; SDM</div>
        @if($u->hasPermission('finance.view'))
        <details class="side-group" @if(request()->routeIs('finance.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6.5 9.5h.01M17.5 14.5h.01"/></svg>
            <span>Keuangan</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('finance.journals.*') }}" href="{{ route('finance.journals.index') }}">Jurnal</a>
            <a class="{{ $isActive('finance.expenses.*') }}" href="{{ route('finance.expenses.index') }}">Biaya</a>
            <a class="{{ $isActive('finance.ledger') }}" href="{{ route('finance.ledger') }}">Buku Besar</a>
            <a class="{{ $isActive('finance.trial') }}" href="{{ route('finance.trial') }}">Neraca Saldo</a>
            <a class="{{ $isActive('finance.pl') }}" href="{{ route('finance.pl') }}">Laba Rugi</a>
            <a class="{{ $isActive('finance.budget') }}" href="{{ route('finance.budget') }}">Budget vs Aktual</a>
            <a class="{{ $isActive('finance.bankrec.*') }}" href="{{ route('finance.bankrec.index') }}">Rekonsiliasi Bank</a>
            <a class="{{ $isActive('finance.aging') }}" href="{{ route('finance.aging') }}">Aging AR/AP</a>
            <a class="{{ $isActive('finance.commissions.*') }}" href="{{ route('finance.commissions.index') }}">Komisi Penjualan</a>
            <a class="{{ $isActive('finance.intercompany') }}" href="{{ route('finance.intercompany') }}">Worksheet Intercompany</a>
            <a class="{{ $isActive('finance.closing') }}" href="{{ route('finance.closing') }}">Tutup Periode</a>
          </div>
        </details>
        @endif
        @if($u->hasPermission('hr.view'))
        <details class="side-group" @if(request()->routeIs('hr.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="3" y="8" width="18" height="12" rx="2"/><path d="M9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13h18"/></svg>
            <span>HR</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('hr.employees.*') }}" href="{{ route('hr.employees.index') }}">Karyawan</a>
            <a class="{{ $isActive('hr.attendance.*') }}" href="{{ route('hr.attendance.index') }}">Absensi</a>
            <a class="{{ $isActive('hr.payroll.*') }}" href="{{ route('hr.payroll.index') }}">Payroll</a>
          </div>
        </details>
        @endif
        @if($u->hasPermission('marketing.view'))
        <details class="side-group" @if(request()->routeIs('marketing.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 10v4h3l9 5V5L7 10z"/><path d="M18.5 8.5a4 4 0 0 1 0 7"/></svg>
            <span>Marketing</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('marketing.campaigns.*') }}" href="{{ route('marketing.campaigns.index') }}">Kampanye</a>
            <a class="{{ $isActive('marketing.vouchers.*') }}" href="{{ route('marketing.vouchers.index') }}">Voucher</a>
          </div>
        </details>
        @endif
      </div>
      @if($u->hasPermission('reports.view') || $u->hasPermission('governance.view'))
      <div class="side-section"><div class="side-label">Laporan &amp; Tata Kelola</div>
        @if($u->hasPermission('reports.view'))
        <details class="side-group" @if(request()->routeIs('reports.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20V4M4 20h16"/><path d="M8 16v-5M12 16V8M16 16v-3"/></svg>
            <span>Laporan</span>
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('reports.sales') }}" href="{{ route('reports.sales') }}">Penjualan</a>
            <a class="{{ $isActive('reports.inventory') }}" href="{{ route('reports.inventory') }}">Inventori</a>
            <a class="{{ $isActive('reports.aging') }}" href="{{ route('reports.aging') }}">Stok Menua</a>
            <a class="{{ $isActive('reports.purchase') }}" href="{{ route('reports.purchase') }}">Pembelian</a>
          </div>
        </details>
        @endif
        @if($u->hasPermission('governance.view'))
        <details class="side-group" @if(request()->routeIs('gov.*')) open @endif>
          <summary class="side-link">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l8 3v6c0 4.5-3.2 7.6-8 9-4.8-1.4-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
            <span>Governance</span>
            @if($pend)<span class="badge bg-yellow ms-auto">{{ $pend }}</span>@endif
            <svg class="chev" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </summary>
          <div class="side-sub">
            <a class="{{ $isActive('gov.approvals.*') }}" href="{{ route('gov.approvals.index') }}">Approval @if($pend)<span class="badge bg-yellow ms-1">{{ $pend }}</span>@endif</a>
            <a class="{{ $isActive('gov.delegations.*') }}" href="{{ route('gov.delegations.index') }}">Delegasi</a>
          </div>
        </details>
        @endif
      </div>
      @endif
    </nav>
    <div class="sidebar-foot">
      <div class="context-card" title="Konteks aktif">
        <div class="context-row"><span class="context-k">Entitas</span><strong>{{ $activeEnt->code ?? '—' }}</strong></div>
        <div class="context-row"><span class="context-k">Cabang</span><strong>{{ $activeBr->code ?? '—' }}</strong></div>
      </div>
    </div>
  </aside>

  <div class="main-col">
    <header class="topbar no-print">
      <button class="btn btn-icon topbar-toggle" id="sidebarToggle" aria-label="Buka/tutup navigasi" aria-controls="sidebar" aria-expanded="false">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <form method="GET" action="{{ route('reports.sales') }}" class="topbar-search d-none d-md-flex" role="search">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
        <input type="search" name="q" class="form-control" placeholder="Cari nomor invoice / pelanggan…" value="{{ request('q') }}" aria-label="Cari invoice">
      </form>
      <div class="topbar-right">
        <form method="POST" action="{{ route('context.switch') }}" class="topbar-context d-none d-lg-flex" title="Konteks entitas &amp; cabang">@csrf
          <label class="visually-hidden" for="ctxEnt">Badan usaha aktif</label>
          <select name="legal_entity_id" id="ctxEnt" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($ents as $e)<option value="{{ $e->id }}" @selected($activeEid==$e->id)>{{ $e->code }}</option>@endforeach
          </select>
          <label class="visually-hidden" for="ctxBr">Cabang aktif</label>
          <select name="branch_id" id="ctxBr" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($brs as $b)<option value="{{ $b->id }}" @selected($activeBid==$b->id)>{{ $b->code }}</option>@endforeach
          </select>
        </form>
        <a href="{{ route('gov.approvals.index') }}" class="btn btn-icon position-relative" title="Persetujuan tertunda" aria-label="Persetujuan tertunda">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l8 3v6c0 4.5-3.2 7.6-8 9-4.8-1.4-8-4.5-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg>
          @if($pend)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning">{{ $pend }}</span>@endif
        </a>
        <div class="nav-item dropdown">
          <a href="#" class="btn btn-icon position-relative" data-bs-toggle="dropdown" title="Notifikasi" aria-label="Notifikasi" aria-haspopup="true">
            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/></svg>
            @if($notifCount)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $notifCount }}</span>@endif
          </a>
          <div class="dropdown-menu dropdown-menu-end" style="min-width:280px">
            @foreach($notif as $n)<a class="dropdown-item" href="{{ route('notifications.read',$n->id) }}">{{ Str::limit($n->title,45) }}</a>@endforeach
            @if(!$notif->count())<span class="dropdown-item-text text-muted">Tidak ada notifikasi baru.</span>@endif
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-center" href="{{ route('notifications.index') }}">Semua notifikasi</a>
          </div>
        </div>
        <div class="nav-item dropdown">
          <a href="#" class="btn profile-btn" data-bs-toggle="dropdown" aria-haspopup="true" title="{{ $u->name }}">
            <span class="avatar avatar-sm">{{ mb_substr($u->name,0,1) }}</span>
            <span class="profile-name d-none d-xl-inline">{{ Str::limit($u->name,18) }}</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
          </a>
          <div class="dropdown-menu dropdown-menu-end">
            <span class="dropdown-item-text"><strong>{{ $u->name }}</strong><br><span class="text-muted">{{ $activeEnt->code ?? '' }} · {{ $activeBr->code ?? '' }}</span></span>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="{{ route('profile') }}">Profil</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item">Keluar</button></form>
          </div>
        </div>
      </div>
    </header>
    <div class="page-wrapper">
      <main class="page-body" id="main-content" tabindex="-1">
        <div class="container-xl">
          @if(session('ok'))<div class="alert alert-success alert-dismissible">{{ session('ok') }}<button class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button></div>@endif
          @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
          @yield('content')
        </div>
      </main>
    </div>
  </div>
</div>
</body>
</html>

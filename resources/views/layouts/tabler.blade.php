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
<div class="page">
<header class="navbar navbar-expand-md d-print-none no-print">
<div class="container-xl">
<button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navbar-menu">☰</button>
<a href="{{ route('dashboard') }}" class="navbar-brand">KazaERP <span class="badge bg-blue">Kazaka Group</span></a>
<div class="navbar-nav flex-row order-md-last ms-auto">
<form method="GET" action="{{ route('reports.sales') }}" class="me-2 d-none d-md-block">
<input type="search" name="q" class="form-control" placeholder="Cari invoice…" value="{{ request('q') }}">
</form>
@php $pend = DB::table('approvals')->where('status','pending')->count();
$notif = DB::table('notifications')->where('user_id', auth()->id())->whereNull('read_at')->orderByDesc('id')->limit(5)->get();
$notifCount = DB::table('notifications')->where('user_id', auth()->id())->whereNull('read_at')->count(); @endphp
<div class="nav-item dropdown">
<a href="#" class="nav-link" data-bs-toggle="dropdown" title="Notifikasi">🔔 @if($notifCount)<span class="badge bg-red">{{ $notifCount }}</span>@endif</a>
<div class="dropdown-menu dropdown-menu-end" style="min-width:280px">
@foreach($notif as $n)<a class="dropdown-item" href="{{ route('notifications.read',$n->id) }}">{{ Str::limit($n->title,45) }}</a>@endforeach
@if(!$notif->count())<span class="dropdown-item-text text-muted">Tidak ada notifikasi baru.</span>@endif
<div class="dropdown-divider"></div>
<a class="dropdown-item text-center" href="{{ route('notifications.index') }}">Semua notifikasi</a>
</div>
</div>
<a href="{{ route('gov.approvals.index') }}" class="nav-link" title="Persetujuan">✅ @if($pend)<span class="badge bg-yellow">{{ $pend }}</span>@endif</a>
<div class="nav-item dropdown">
<a href="#" class="nav-link d-flex" data-bs-toggle="dropdown">{{ auth()->user()->name }} ▾</a>
<div class="dropdown-menu dropdown-menu-end">
<a class="dropdown-item" href="{{ route('profile') }}">Profil</a>
<form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item">Keluar</button></form>
</div>
</div>
</div>
</div>
</header>
<div class="navbar-expand-md no-print">
<div class="collapse navbar-collapse" id="navbar-menu">
<div class="navbar navbar-light">
<div class="container-xl">
<ul class="navbar-nav">
@php $u = auth()->user(); @endphp
<li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}">🏠 Dashboard</a></li>
@if($u->hasPermission('org.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">🏢 Organisasi</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('org.holdings.index') }}">Holding</a>
<a class="dropdown-item" href="{{ route('org.entities.index') }}">Badan Usaha</a>
<a class="dropdown-item" href="{{ route('org.branches.index') }}">Cabang</a>
<a class="dropdown-item" href="{{ route('org.warehouses.index') }}">Gudang</a>
</div></li>
@endif
@if($u->hasPermission('users.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">👥 Akses</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('users.index') }}">Pengguna</a>
<a class="dropdown-item" href="{{ route('roles.index') }}">Peran</a>
</div></li>
@endif
@if($u->hasPermission('crm.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">🤝 CRM</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('crm.customers.index') }}">Pelanggan</a>
<a class="dropdown-item" href="{{ route('crm.suppliers.index') }}">Supplier</a>
<a class="dropdown-item" href="{{ route('crm.leads.index') }}">Lead</a>
<a class="dropdown-item" href="{{ route('crm.opportunities.index') }}">Opportunity</a>
<a class="dropdown-item" href="{{ route('master.products.index') }}">Produk</a>
</div></li>
@endif
@if($u->hasPermission('sales.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">🧾 Penjualan</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('sales.quotes.index') }}">Quotation</a>
<a class="dropdown-item" href="{{ route('sales.orders.index') }}">Sales Order</a>
<a class="dropdown-item" href="{{ route('sales.deliveries.index') }}">Delivery</a>
<a class="dropdown-item" href="{{ route('sales.invoices.index') }}">Invoice</a>
<a class="dropdown-item" href="{{ route('sales.creditnotes.index') }}">Credit Note</a>
<a class="dropdown-item" href="{{ route('sales.returns.index') }}">Retur</a>
@if($u->hasPermission('pos.operate'))<a class="dropdown-item" href="{{ route('sales.pos.index') }}">POS Kasir</a>@endif
</div></li>
@endif
@if($u->hasPermission('inventory.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">📦 Inventori</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('inventory.stock.index') }}">Stok & Kartu Stok</a>
<a class="dropdown-item" href="{{ route('inventory.receipts.index') }}">Penerimaan</a>
</div></li>
@endif
@if($u->hasPermission('purchasing.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">🛒 Pembelian</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('purchasing.requisitions.index') }}">Permintaan</a>
<a class="dropdown-item" href="{{ route('purchasing.orders.index') }}">Purchase Order</a>
<a class="dropdown-item" href="{{ route('purchasing.vinvoices.index') }}">Invoice & Bayar Vendor</a>
</div></li>
@endif
@if($u->hasPermission('finance.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">💰 Keuangan</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('finance.journals.index') }}">Jurnal</a>
<a class="dropdown-item" href="{{ route('finance.expenses.index') }}">Biaya</a>
<a class="dropdown-item" href="{{ route('finance.ledger') }}">Buku Besar</a>
<a class="dropdown-item" href="{{ route('finance.trial') }}">Neraca Saldo</a>
<a class="dropdown-item" href="{{ route('finance.pl') }}">Laba Rugi</a>
<a class="dropdown-item" href="{{ route('finance.aging') }}">Aging AR/AP</a>
<a class="dropdown-item" href="{{ route('finance.closing') }}">Tutup Periode</a>
</div></li>
@endif
@if($u->hasPermission('hr.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">🧑‍💼 HR</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('hr.employees.index') }}">Karyawan</a>
<a class="dropdown-item" href="{{ route('hr.attendance.index') }}">Absensi</a>
<a class="dropdown-item" href="{{ route('hr.payroll.index') }}">Payroll</a>
</div></li>
@endif
@if($u->hasPermission('pet.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">🐾 Pet Care</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('pet.owners.index') }}">Pemilik</a>
<a class="dropdown-item" href="{{ route('pet.pets.index') }}">Hewan</a>
<a class="dropdown-item" href="{{ route('pet.vet.index') }}">Veteriner</a>
<a class="dropdown-item" href="{{ route('pet.grooming.index') }}">Grooming</a>
<a class="dropdown-item" href="{{ route('pet.boarding.index') }}">Pet Hotel</a>
<a class="dropdown-item" href="{{ route('pet.transport.index') }}">Transport</a>
</div></li>
@endif
@if($u->hasPermission('mfg.view')||$u->hasPermission('thrift.view')||$u->hasPermission('studio.view')||$u->hasPermission('rental.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">🏭 Bisnis</a>
<div class="dropdown-menu">
@if($u->hasPermission('mfg.view'))<a class="dropdown-item" href="{{ route('mfg.designs.index') }}">Desain Apparel</a>@endif
@if($u->hasPermission('mfg.view'))<a class="dropdown-item" href="{{ route('mfg.boms.index') }}">BOM</a>@endif
@if($u->hasPermission('mfg.plan'))<a class="dropdown-item" href="{{ route('mfg.workorders.index') }}">Work Order</a>@endif
@if($u->hasPermission('thrift.view'))<a class="dropdown-item" href="{{ route('thrift.lots.index') }}">Lot Thrift</a>@endif
@if($u->hasPermission('thrift.view'))<a class="dropdown-item" href="{{ route('thrift.items.index') }}">Item Thrift</a>@endif
@if($u->hasPermission('studio.view'))<a class="dropdown-item" href="{{ route('studio.projects.index') }}">Studio</a>@endif
@if($u->hasPermission('rental.view'))<a class="dropdown-item" href="{{ route('rental.catalogs.index') }}">Katalog Sewa</a>@endif
@if($u->hasPermission('rental.view'))<a class="dropdown-item" href="{{ route('rental.reservations.index') }}">Reservasi Sewa</a>@endif
</div></li>
@endif
@if($u->hasPermission('marketing.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">📣 Marketing</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('marketing.campaigns.index') }}">Kampanye</a>
<a class="dropdown-item" href="{{ route('marketing.vouchers.index') }}">Voucher</a>
</div></li>
@endif
@if($u->hasPermission('reports.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">📊 Laporan</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('reports.sales') }}">Penjualan</a>
<a class="dropdown-item" href="{{ route('reports.inventory') }}">Inventori</a>
<a class="dropdown-item" href="{{ route('reports.purchase') }}">Pembelian</a>
</div></li>
@endif
@if($u->hasPermission('governance.view'))
<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">✅ Governance</a>
<div class="dropdown-menu">
<a class="dropdown-item" href="{{ route('gov.approvals.index') }}">Approval</a>
<a class="dropdown-item" href="{{ route('gov.delegations.index') }}">Delegasi</a>
</div></li>
@endif
</ul>
<div class="my-2 my-md-0 flex-grow-1 flex-md-grow-0 order-first order-md-last">
@php
$ctx = session('erp_context',[]);
$ents = $u->hasRole('super_admin','holding_owner','executive') ? DB::table('legal_entities')->orderBy('code')->get() : DB::table('legal_entities')->where('id',$u->legal_entity_id)->get();
$brs = DB::table('branches')->when(!($u->hasRole('super_admin','holding_owner','executive')), fn($q)=>$q->where('id',$u->branch_id))->orderBy('code')->get();
@endphp
<form method="POST" action="{{ route('context.switch') }}" class="d-flex gap-1">@csrf
<select name="legal_entity_id" class="form-select form-select-sm" onchange="this.form.submit()">
@foreach($ents as $e)<option value="{{ $e->id }}" @selected(($ctx['legal_entity_id'] ?? $u->legal_entity_id)==$e->id)>{{ $e->code }}</option>@endforeach
</select>
<select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
@foreach($brs as $b)<option value="{{ $b->id }}" @selected(($ctx['branch_id'] ?? $u->branch_id)==$b->id)>{{ $b->code }}</option>@endforeach
</select>
</form>
</div>
</div>
</div>
</div>
</div>
<div class="page-wrapper">
<div class="page-body">
<div class="container-xl">
@if(session('ok'))<div class="alert alert-success alert-dismissible">{{ session('ok') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
@yield('content')
</div>
</div>
</div>
</div>
</body>
</html>

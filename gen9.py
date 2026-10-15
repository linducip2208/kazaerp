"""Generator 9: layout, auth, dashboard, components, generic CRUD views."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

w("resources/views/layouts/tabler.blade.php", """<!DOCTYPE html>
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
@php $pend = DB::table('approvals')->where('status','pending')->count(); @endphp
<a href="{{ route('gov.approvals.index') }}" class="nav-link" title="Persetujuan">🔔 @if($pend)<span class="badge bg-red">{{ $pend }}</span>@endif</a>
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
<a class="dropdown-item" href="{{ route('purchasing.vinvoices.index') }}">Invoice Vendor</a>
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
<li class="nav-item"><a class="nav-link" href="{{ route('gov.approvals.index') }}">✅ Approval</a></li>
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
""")

w("resources/views/auth/login.blade.php", """<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk — KazaERP</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="d-flex flex-column">
<div class="page page-center"><div class="container container-tight py-4">
<div class="card card-md"><div class="card-body">
<h2 class="h2 text-center mb-4">KazaERP — Kazaka Group</h2>
<p class="text-center text-muted">Masuk ke ERP terpadu</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('login.attempt') }}">@csrf
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus></div>
<div class="mb-2"><label class="form-label">Kata sandi</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label class="form-check"><input type="checkbox" name="remember" class="form-check-input"><span class="form-check-label">Ingat saya</span></label></div>
<button class="btn btn-primary w-100">Masuk</button>
</form>
<p class="text-center text-muted mt-3">Akun demo (non-produksi): admin@kazaka.local</p>
</div></div></div></div></body></html>
""")
w("resources/views/auth/profile.blade.php", """@extends('layouts.tabler')
@section('title','Profil')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Profil Pengguna</h3></div>
<div class="card-body">
<form method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
<div class="mb-3"><label class="form-label">Nama</label><input name="name" class="form-control" value="{{ old('name', auth()->user()->name) }}"></div>
<div class="mb-3"><label class="form-label">Email</label><input class="form-control" value="{{ auth()->user()->email }}" disabled></div>
<div class="mb-3"><label class="form-label">Peran</label><div>@foreach(auth()->user()->roles as $r)<span class="badge bg-blue me-1">{{ $r->label }}</span>@endforeach</div></div>
<button class="btn btn-primary">Simpan</button>
</form></div></div>
@endsection
""")

w("resources/views/dashboard.blade.php", """@extends('layouts.tabler')
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
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Sales Order Terbaru</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th></tr></thead>
<tbody>@forelse($orders as $o)<tr><td><a href="{{ route('sales.orders.show',$o->id) }}">{{ $o->number }}</a></td><td>{{ tgl_id($o->date) }}</td><td class="text-end">{{ idr($o->grand_total) }}</td><td><span class="badge bg-blue">{{ $o->status }}</span></td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada order. <a href="{{ route('sales.orders.create') }}">Buat pertama</a>.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection
""")

# ---------- shared components ----------
w("resources/views/components/crud-index.blade.php", """@php $m = $meta; @endphp
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">{{ $m['title'] }}</h2></div>
<div class="col-auto"><a href="{{ route($m['route'].'.create') }}" class="btn btn-primary no-print">+ Tambah</a></div>
</div></div>
<div class="card mt-2"><div class="card-body border-bottom">
<form method="GET" class="row g-2">
<div class="col-md-4"><input name="q" class="form-control" placeholder="Cari…" value="{{ request('q') }}"></div>
<div class="col-md-3"><select name="status" class="form-select" onchange="this.form.submit()"><option value="">— Semua status —</option>
@foreach(['draft','submitted','pending','open','approved','posted','paid','active','scheduled','reserved'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr>@foreach($m['columns'] as $label)<th>{{ $label }}</th>@endforeach<th></th></tr></thead>
<tbody>
@forelse($rows as $row)<tr>
@foreach(array_keys($m['columns']) as $col)
<td>@if(str_contains($col,'price')||str_contains($col,'amount')||str_contains($col,'total')){{ idr($row->$col ?? 0) }}@elseif($col==='status'||$col==='pay_status')<span class="badge bg-blue">{{ $row->$col }}</span>@elseif($col==='is_active'){{ ($row->$col)?'Ya':'Tidak' }}@else{{ Str::limit($row->$col ?? '-',60) }}@endif</td>
@endforeach
<td class="text-end"><a href="{{ route($m['route'].'.show',$row->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
</tr>
@empty<tr><td colspan="{{ count($m['columns'])+1 }}"><div class="empty-state">Belum ada data. Klik <strong>+ Tambah</strong> untuk membuat pertama.</div></td></tr>
@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>
""")
w("resources/views/components/crud-form.blade.php", """<div class="card"><div class="card-header"><h3 class="card-title">{{ $meta['title'] }}</h3></div>
<div class="card-body">
<form method="POST" action="{{ $action }}">@csrf @if($method!=='POST')@method($method)@endif
<div class="row">
@foreach($meta['fields'] as $f)
<div class="mb-3 col-md-6">
<label class="form-label">{{ $f['label'] }}</label>
@if($f['type']==='textarea')
<textarea name="{{ $f['name'] }}" class="form-control">{{ old($f['name'], $row->{$f['name']} ?? '') }}</textarea>
@elseif($f['type']==='select' && isset($f['options']))
<select name="{{ $f['name'] }}" class="form-select">@foreach($f['options'] as $v=>$l)<option value="{{ $v }}" @selected((string)old($f['name'], $row->{$f['name']} ?? '')===(string)$v)>{{ $l }}</option>@endforeach</select>
@elseif($f['type']==='select_model')
@php $mm=$f['model']; $ol=$f['option_label']??'name'; $opts=$mm::orderBy('id')->limit(300)->get(); @endphp
<select name="{{ $f['name'] }}" class="form-select"><option value="">— Pilih —</option>@foreach($opts as $o)<option value="{{ $o->id }}" @selected((string)old($f['name'], $row->{$f['name']} ?? '')===(string)$o->id)>{{ $o->$ol ?? $o->id }}</option>@endforeach</select>
@elseif($f['type']==='date')
<input type="date" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], isset($row->{$f['name']})&&$row->{$f['name']} ? substr((string)$row->{$f['name']},0,10) : '') }}">
@elseif($f['type']==='number')
<input type="number" step="any" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], $row->{$f['name']} ?? '') }}">
@elseif($f['type']==='password')
<input type="password" name="{{ $f['name'] }}" class="form-control" autocomplete="new-password">
@elseif($f['type']==='email')
<input type="email" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], $row->{$f['name']} ?? '') }}">
@else
<input type="text" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], $row->{$f['name']} ?? '') }}">
@endif
</div>
@endforeach
</div>
<div class="mt-2"><button class="btn btn-primary">Simpan</button>
<a href="{{ route($meta['route'].'.index') }}" class="btn btn-link">Batal</a></div>
</form></div></div>
""")
w("resources/views/components/crud-show.blade.php", """<div class="card"><div class="card-header"><div class="row align-items-center">
<div class="col"><h3 class="card-title">{{ $meta['title'] }} #{{ $row->number ?? $row->code ?? $row->id }}</h3></div>
<div class="col-auto no-print">
<a href="{{ route($meta['route'].'.edit',$row->id) }}" class="btn btn-outline-primary">Ubah</a>
<form method="POST" action="{{ route($meta['route'].'.destroy',$row->id) }}" class="d-inline" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Hapus</button></form>
</div></div></div>
<div class="card-body"><dl class="row">
@foreach($meta['columns'] as $col=>$label)<dt class="col-sm-3">{{ $label }}</dt><dd class="col-sm-9">{{ $row->$col ?? '-' }}</dd>@endforeach
</dl>{{ $slot ?? '' }}</div></div>
""")
w("resources/views/components/doc-lines-form.blade.php", """<div class="card mt-3"><div class="card-header"><h3 class="card-title">Baris Transaksi</h3></div>
<div class="card-body">
<table class="table" id="lines-table"><thead><tr><th>Produk</th><th style="width:120px">Qty</th><th style="width:160px">Harga</th><th></th></tr></thead>
<tbody></tbody></table>
<button type="button" class="btn btn-outline-primary" id="add-line">+ Baris</button>
</div></div>
<script>
(function(){
const prods = @json($products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'price'=>$p->sell_price ?? $p->price ?? 0]));
const tb = document.querySelector('#lines-table tbody');
function addRow(){
const tr = document.createElement('tr');
let opts = prods.map(p=>`<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('');
tr.innerHTML = `<td><select name="lines[][product_id]" class="form-select prod">${opts}</select></td>
<td><input name="lines[][qty]" type="number" step="any" min="0.0001" value="1" class="form-control qty"></td>
<td><input name="lines[][price]" type="number" step="any" min="0" value="0" class="form-control price"></td>
<td><button type="button" class="btn btn-sm btn-outline-danger rm">×</button></td>`;
tb.appendChild(tr);
const sel = tr.querySelector('.prod'), pr = tr.querySelector('.price');
const sync = ()=>{ pr.value = sel.selectedOptions[0].dataset.price; };
sel.addEventListener('change', sync); sync();
tr.querySelector('.rm').addEventListener('click', ()=>tr.remove());
}
// index lines[] dengan urutan: browser mengirim lines[0..n] otomatis? gunakan counter:
let i = 0;
document.getElementById('add-line').addEventListener('click', ()=>{
// tulis ulang name dengan index agar Laravel parse array rapi
addRow();
[...tb.rows].forEach((tr,idx)=>{
tr.querySelector('.prod').name = `lines[${idx}][product_id]`;
tr.querySelector('.qty').name = `lines[${idx}][qty]`;
tr.querySelector('.price').name = `lines[${idx}][price]`;
});
});
addRow();
})();
</script>
""")

# ---------- generic wrappers for all simple prefixes ----------
PREFIXES = [
 "modules.org.holdings","modules.org.entities","modules.org.branches","modules.org.warehouses",
 "modules.users","modules.roles",
 "modules.master.products","modules.master.categories",
 "modules.crm.customers","modules.crm.suppliers","modules.crm.leads","modules.crm.opportunities",
 "modules.hr.employees",
 "modules.pet.owners","modules.pet.pets","modules.pet.vet","modules.pet.grooming","modules.pet.boarding","modules.pet.transport",
 "modules.mfg.designs","modules.mfg.boms","modules.mfg.workorders",
 "modules.thrift.lots","modules.thrift.items",
 "modules.studio.projects",
 "modules.rental.catalogs","modules.rental.reservations",
 "modules.marketing.campaigns","modules.marketing.vouchers",
 "modules.finance.expenses",
 "modules.sales.quotes","modules.sales.deliveries","modules.sales.creditnotes","modules.sales.returns",
]
for px in PREFIXES:
    base = "resources/views/" + px.replace(".","/")
    w(base+"/index.blade.php", """@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-index', ['rows'=>$rows,'meta'=>$meta])
@endsection
""")
    w(base+"/form.blade.php", """@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-form', ['row'=>$row,'meta'=>$meta,'method'=>$method,'action'=>$action])
@endsection
""")
    w(base+"/show.blade.php", """@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-show', ['row'=>$row,'meta'=>$meta])
@endsection
""")
print("gen9 done")

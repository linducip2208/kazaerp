"""Generator 10: custom doc views, reports, print, errors, PWA."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

DOC_FORM = """@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">{{ $meta['title'] }} Baru</h3></div>
<div class="card-body">
<form method="POST" action="ACTION">@csrf
<div class="row">
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
EXTRA
<div class="mb-3 col-md-4"><label class="form-label">Pelanggan</label><select name="customer_id" class="form-select" required>@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
</div>
@include('components.doc-lines-form', ['products'=>$products])
<div class="mt-3"><button class="btn btn-primary">Simpan</button></div>
</form></div></div>
@endsection
"""
DOC_SHOW = """@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center">
<div class="col"><h3 class="card-title">{{ $meta['title'] }} — {{ $row->number }}</h3>
<div class="text-muted">{{ tgl_id($row->date) }} • <span class="badge bg-blue">{{ $row->status }}</span></div></div>
<div class="col-auto no-print">ACTIONBTN</div></div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Total</th></tr></thead>
<tbody>@foreach($row->lines ?? $lines ?? [] as $l)<tr><td>{{ $l->snapshot_name ?? $l->product->name ?? '-' }}</td><td class="text-end">{{ $l->qty }}</td><td class="text-end">{{ idr($l->price) }}</td><td class="text-end">{{ idr($l->line_total) }}</td></tr>@endforeach</tbody>
<tfoot><tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end">{{ idr($row->subtotal ?? 0) }}</th></tr>
<tr><th colspan="3" class="text-end">Pajak</th><th class="text-end">{{ idr($row->tax ?? 0) }}</th></tr>
<tr><th colspan="3" class="text-end">Grand Total</th><th class="text-end">{{ idr($row->grand_total ?? 0) }}</th></tr></tfoot></table></div></div>
SLOT
@endsection
"""

def docform(action, extra=""):
    return DOC_FORM.replace("ACTION", action).replace("EXTRA", extra)

# sales docs
for name, route in [("orders","sales.orders"),("invoices","sales.invoices"),("quotes","sales.quotes"),("deliveries","sales.deliveries"),("creditnotes","sales.creditnotes"),("returns","sales.returns")]:
    w(f"resources/views/modules/sales/{name}/form.blade.php", docform("{{ route('"+route+".store') }}",
        '<div class="mb-3 col-md-4"><label class="form-label">Jatuh Tempo (invoice)</label><input type="date" name="due_date" class="form-control"></div>' if name=="invoices" else ""))
    btn = ""
    if name=="orders": btn = """<form method="POST" action="{{ route('sales.orders.approve',$row->id) }}" class="d-inline">@csrf<button class="btn btn-primary">Setujui + Reservasi</button></form>
<form method="POST" action="{{ route('sales.orders.cancel',$row->id) }}" class="d-inline" onsubmit="return confirm('Batalkan SO?')">@csrf<button class="btn btn-outline-danger">Batal</button></form>"""
    if name=="invoices": btn = """<a href="{{ route('sales.invoices.pdf',$row->id) }}" class="btn btn-outline-primary">PDF</a>
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#payModal">Bayar</button>
<div class="modal fade" id="payModal"><div class="modal-dialog"><form method="POST" action="{{ route('sales.invoices.pay',$row->id) }}" class="modal-content">@csrf
<div class="modal-header"><h5 class="modal-title">Catat Pembayaran</h5></div>
<div class="modal-body"><div class="mb-2"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-2"><label class="form-label">Nominal (sisa: {{ idr($row->grand_total-$row->paid_total) }})</label><input type="number" step="any" name="amount" class="form-control" required></div>
<div class="mb-2"><label class="form-label">Metode</label><select name="method" class="form-select"><option value="cash">Tunai</option><option value="transfer">Transfer</option><option value="deposit">Deposit</option></select></div></div>
<div class="modal-footer"><button class="btn btn-primary">Simpan</button></div></form></div></div>"""
    slot = ""
    if name=="invoices": slot = """<div class="card mt-3"><div class="card-header"><h3 class="card-title">Pembayaran</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Metode</th><th class="text-end">Nominal</th></tr></thead>
<tbody>@foreach($row->payments as $p)<tr><td>{{ tgl_id($p->date) }}</td><td>{{ $p->method }}</td><td class="text-end">{{ idr($p->amount) }}</td></tr>@endforeach</tbody></table></div></div>"""
    if name=="orders": slot = ""
    w(f"resources/views/modules/sales/{name}/index.blade.php", """@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">{{ $meta['title'] }}</h2></div>
<div class="col-auto"><a href="{{ route('""" + route + """.create') }}" class="btn btn-primary">+ Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td><td class="text-end"><a href="{{ route('""" + route + """.show',$r->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada data.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
    show = DOC_SHOW.replace("ACTIONBTN", btn)
    show = show.replace("SLOT", slot)
    # orders/invoices show need $row->lines relation; generic show uses $lines fallback ok
    w(f"resources/views/modules/sales/{name}/show.blade.php", show)

# ---------- POS ----------
w("resources/views/modules/sales/pos/index.blade.php", """@extends('layouts.tabler')
@section('title','POS Kasir')
@section('content')
@if(!$shift)
<div class="card"><div class="card-body">
<h3>Buka Shift Kasir</h3>
<form method="POST" action="{{ route('sales.pos.open') }}">@csrf
<div class="mb-3 col-md-3"><label class="form-label">Kas awal (Rp)</label><input type="number" name="opening_cash" class="form-control" required></div>
<button class="btn btn-primary">Buka Shift</button></form></div></div>
@else
<div class="row">
<div class="col-md-7"><div class="card"><div class="card-header"><h3 class="card-title">Kasir — Shift #{{ $shift->id }}</h3></div>
<div class="card-body">
<form method="POST" action="{{ route('sales.pos.checkout') }}">@csrf
<div class="mb-2"><label class="form-label">Produk (klik untuk tambah)</label>
<div class="row">@foreach($products as $p)<div class="col-6 mb-1"><button type="button" class="btn btn-outline-secondary w-100 addp" data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-price="{{ $p->sell_price }}">{{ $p->name }} — {{ idr($p->sell_price) }}</button></div>@endforeach</div></div>
<table class="table" id="cart"><thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th></th></tr></thead><tbody></tbody></table>
<div class="mb-2"><label class="form-label">Metode</label><select name="pay_method" class="form-select"><option value="cash">Tunai</option><option value="transfer">Transfer</option><option value="qris">QRIS</option></select></div>
<button class="btn btn-primary">Bayar / Checkout</button></form>
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header"><h3 class="card-title">Tutup Shift</h3></div><div class="card-body">
<form method="POST" action="{{ route('sales.pos.close') }}">@csrf
<div class="mb-2"><label class="form-label">Kas akhir aktual</label><input type="number" name="closing_cash" class="form-control" required></div>
<button class="btn btn-warning" onclick="return confirm('Tutup shift?')">Tutup Shift</button></form></div></div></div>
</div>
<script>
const cart = document.querySelector('#cart tbody'); let i = 0;
document.querySelectorAll('.addp').forEach(b=>b.addEventListener('click',()=>{
const tr = document.createElement('tr');
tr.innerHTML = `<td>${b.dataset.name}<input type="hidden" name="lines[${i}][product_id]" value="${b.dataset.id}"></td>
<td><input type="number" name="lines[${i}][qty]" value="1" min="0.01" step="any" class="form-control"></td>
<td>${b.dataset.price}</td><td><button type="button" class="btn btn-sm rm">×</button></td>`;
tr.querySelector('.rm').onclick = ()=>tr.remove(); cart.appendChild(tr); i++;
}));
</script>
@endif
@endsection
""")

# ---------- purchasing views ----------
w("resources/views/modules/purchasing/orders/form.blade.php", docform("{{ route('purchasing.orders.store') }}",
  '<div class="mb-3 col-md-4"><label class="form-label">Supplier</label><select name="supplier_id" class="form-select" required>@foreach($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>').replace("Pelanggan","Supplier").replace("customer_id","supplier_id").replace("$customers","$suppliers").replace("$c->name","$s->name").replace("$c->id","$s->id"))
w("resources/views/modules/purchasing/orders/index.blade.php", """@extends('layouts.tabler')
@section('title','Purchase Order')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Purchase Order</h2></div>
<div class="col-auto"><a href="{{ route('purchasing.orders.create') }}" class="btn btn-primary">+ Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td><td class="text-end"><a href="{{ route('purchasing.orders.show',$r->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada PO.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/purchasing/orders/show.blade.php", """@extends('layouts.tabler')
@section('title','Purchase Order')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center">
<div class="col"><h3 class="card-title">PO {{ $row->number }} — <span class="badge bg-blue">{{ $row->status }}</span></h3></div>
<div class="col-auto"><form method="POST" action="{{ route('purchasing.orders.approve',$row->id) }}">@csrf<button class="btn btn-primary">Setujui</button></form>
<a href="{{ route('purchasing.vinvoices.create') }}?from_po={{ $row->id }}" class="btn btn-outline-primary">Buat Invoice Vendor</a>
<a href="{{ route('inventory.receipts.create') }}" class="btn btn-outline-secondary">Terima Barang</a></div></div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Produk</th><th class="text-end">Qty</th><th class="text-end">Diterima</th><th class="text-end">Harga</th></tr></thead>
<tbody>@foreach($row->lines as $l)<tr><td>{{ $l->product->name ?? '-' }}</td><td class="text-end">{{ $l->qty }}</td><td class="text-end">{{ $l->qty_received }}</td><td class="text-end">{{ idr($l->price) }}</td></tr>@endforeach</tbody></table></div></div>
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Invoice Vendor Terkait</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Status</th><th>Match</th></tr></thead>
<tbody>@forelse($invoices as $v)<tr><td>{{ $v->number }}</td><td>{{ $v->status }}</td><td>{{ $v->match_status }}</td></tr>@empty<tr><td colspan="3"><div class="empty-state">Belum ada invoice.</div></td></tr>@endforelse</tbody></table></div></div>
@endsection
""")
w("resources/views/modules/purchasing/requisitions/index.blade.php", """@extends('layouts.tabler')
@section('title','Permintaan Pembelian')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Permintaan Pembelian</h2></div>
<div class="col-auto"><a href="{{ route('purchasing.requisitions.create') }}" class="btn btn-primary">+ Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Status</th><th>Darurat</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td><td>{{ $r->is_emergency?'Ya':'Tidak' }}</td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada data.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/purchasing/requisitions/form.blade.php", """@extends('layouts.tabler')
@section('title','Permintaan Pembelian')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Permintaan Baru</h3></div><div class="card-body">
<form method="POST" action="{{ route('purchasing.requisitions.store') }}">@csrf
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-4"><label class="form-label">Cost Center</label><select name="cost_center_id" class="form-select"><option value="">—</option>@foreach($ccs as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="mb-3 col-md-4"><label class="form-check mt-4"><input type="checkbox" name="is_emergency" value="1" class="form-check-input"><span class="form-check-label">Darurat</span></label><input name="emergency_reason" class="form-control mt-1" placeholder="Alasan darurat (wajib jika darurat)"></div></div>
@include('components.doc-lines-form', ['products'=>$products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'sell_price'=>0])])
<div class="mt-2"><label class="form-label">Estimasi harga per baris diisi setelah produk dipilih (kolom Harga = est_price).</label></div>
<button class="btn btn-primary mt-2">Ajukan</button></form></div></div>
@endsection
""")
w("resources/views/modules/purchasing/vinvoices/index.blade.php", """@extends('layouts.tabler')
@section('title','Invoice Vendor')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Invoice Vendor</h2></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Supplier</th><th class="text-end">Total</th><th>Status</th><th>Match</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ $r->supplier }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td><td>{{ $r->match_status }}</td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Belum ada data.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/purchasing/vinvoices/form.blade.php", """@extends('layouts.tabler')
@section('title','Invoice Vendor')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Invoice Vendor dari PO @if($po){{ $po->number }}@endif</h3></div><div class="card-body">
<form method="POST" action="{{ route('purchasing.vinvoices.store') }}">@csrf
<input type="hidden" name="purchase_order_id" value="{{ $po->id ?? '' }}">
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">No. Invoice Vendor (ref)</label><input name="vendor_ref" class="form-control" required></div>
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-4"><label class="form-label">Jatuh Tempo</label><input type="date" name="due_date" class="form-control"></div></div>
@if(isset($lines))<table class="table"><thead><tr><th>Produk</th><th>Qty PO</th></tr></thead><tbody>@foreach($lines as $l)<tr><td>{{ $l->name }}</td><td>{{ $l->qty }}</td></tr>@endforeach</tbody></table>@endif
<button class="btn btn-primary">Simpan (auto three-way matching)</button></form></div></div>
@endsection
""")

# ---------- inventory views ----------
w("resources/views/modules/inventory/stock/index.blade.php", """@extends('layouts.tabler')
@section('title','Stok')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Stok</h2></div>
<div class="col-auto"><a href="{{ route('inventory.stock.export') }}" class="btn btn-outline-primary">Export CSV</a>
<a href="{{ route('inventory.receipts.create') }}" class="btn btn-primary">+ Penerimaan</a></div></div></div>
<div class="card mt-2"><div class="card-body border-bottom"><form method="GET" class="row g-2">
<div class="col-md-4"><input name="q" class="form-control" placeholder="Cari SKU/nama…" value="{{ request('q') }}"></div>
<div class="col-md-3"><select name="filter" class="form-select" onchange="this.form.submit()"><option value="">Semua</option><option value="low" @selected(request('filter')==='low')>Menipis (≤5 tersedia)</option><option value="quarantine" @selected(request('filter')==='quarantine')>Karantina</option></select></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Gudang</th><th>SKU</th><th>Produk</th><th class="text-end">On Hand</th><th class="text-end">Reserved</th><th class="text-end">Tersedia</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->wh }}</td><td>{{ $r->sku }}</td><td>{{ $r->product }}</td>
<td class="text-end">{{ $r->qty_on_hand }}</td><td class="text-end">{{ $r->qty_reserved }}</td><td class="text-end">{{ $r->qty_on_hand-$r->qty_reserved }}</td>
<td><a href="{{ route('inventory.stock.card',$r->product_id) }}" class="btn btn-sm btn-outline-primary">Kartu</a></td></tr>
@empty<tr><td colspan="7"><div class="empty-state">Belum ada stok.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/inventory/stock/card.blade.php", """@extends('layouts.tabler')
@section('title','Kartu Stok')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Kartu Stok — {{ $product->sku }} / {{ $product->name }}</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Gudang</th><th class="text-end">Qty</th><th>Referensi</th><th>Catatan</th></tr></thead>
<tbody>@foreach($rows as $r)<tr><td>{{ tgl_id($r->created_at,true) }}</td><td>{{ $r->warehouse_id }}</td><td class="text-end">{{ $r->qty }}</td><td>{{ $r->ref_type }} #{{ $r->ref_id }}</td><td>{{ $r->note }}</td></tr>@endforeach</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/inventory/receipt/index.blade.php", """@extends('layouts.tabler')
@section('title','Penerimaan Barang')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Penerimaan (mendukung partial)</h2></div>
<div class="col-auto"><a href="{{ route('inventory.receipts.create') }}" class="btn btn-primary">+ Terima</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Ref</th><th>Status</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td>{{ $r->ref_type }} #{{ $r->ref_id }}</td><td>{{ $r->status }}</td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada penerimaan.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/inventory/receipt/form.blade.php", """@extends('layouts.tabler')
@section('title','Penerimaan Barang')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Penerimaan Baru</h3></div><div class="card-body">
<form method="POST" action="{{ route('inventory.receipts.store') }}">@csrf
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">Gudang</label><select name="warehouse_id" class="form-select">@foreach($warehouses as $x)<option value="{{ $x->id }}">{{ $x->code }} — {{ $x->name }}</option>@endforeach</select></div>
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-4"><label class="form-label">PO (opsional, untuk partial)</label><select name="purchase_order_id" class="form-select"><option value="">— Tanpa PO —</option>@foreach($pos as $p)<option value="{{ $p->id }}">{{ $p->number }}</option>@endforeach</select></div></div>
@include('components.doc-lines-form', ['products'=>$products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name,'sell_price'=>0])])
<button class="btn btn-primary mt-2">Simpan ke Ledger</button></form></div></div>
@endsection
""")

# ---------- finance views ----------
w("resources/views/modules/finance/journals/index.blade.php", """@extends('layouts.tabler')
@section('title','Jurnal')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Jurnal Umum</h2></div>
<div class="col-auto"><a href="{{ route('finance.journals.create') }}" class="btn btn-primary">+ Jurnal</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Deskripsi</th><th class="text-end">Debit</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td>{{ $r->description }}</td><td class="text-end">{{ idr($r->total_debit) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td>
<td><form method="POST" action="{{ route('finance.journals.reverse',$r->id) }}" class="d-inline">@csrf<input type="hidden" name="reason" value="koreksi"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Reversal jurnal ini?')">Reversal</button></form></td></tr>
@empty<tr><td colspan="6"><div class="empty-state">Belum ada jurnal.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/finance/journals/form.blade.php", """@extends('layouts.tabler')
@section('title','Jurnal Baru')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Jurnal (wajib balance)</h3></div><div class="card-body">
<form method="POST" action="{{ route('finance.journals.store') }}">@csrf
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-8"><label class="form-label">Deskripsi</label><input name="description" class="form-control" required></div></div>
<table class="table" id="jtab"><thead><tr><th>Akun</th><th>Debit</th><th>Kredit</th><th></th></tr></thead><tbody></tbody></table>
<button type="button" class="btn btn-outline-primary" id="jadd">+ Baris</button>
<button class="btn btn-primary">Posting</button></form></div></div>
<script>
const coas = @json($coas->map(fn($c)=>['id'=>$c->id,'label'=>$c->code.' — '.$c->name]));
const tb = document.querySelector('#jtab tbody');
function jrow(){
const tr = document.createElement('tr');
tr.innerHTML = `<td><select class="form-select" data-n="coa_id">${coas.map(c=>`<option value="${c.id}">${c.label}</option>`).join('')}</select></td>
<td><input type="number" step="any" min="0" value="0" class="form-control" data-n="debit"></td>
<td><input type="number" step="any" min="0" value="0" class="form-control" data-n="credit"></td>
<td><button type="button" class="btn btn-sm rm">×</button></td>`;
tr.querySelector('.rm').onclick=()=>tr.remove(); tb.appendChild(tr); renumber();
}
function renumber(){ [...tb.rows].forEach((tr,i)=>{ tr.querySelector('[data-n=coa_id]').name=`lines[${i}][coa_id]`; tr.querySelector('[data-n=debit]').name=`lines[${i}][debit]`; tr.querySelector('[data-n=credit]').name=`lines[${i}][credit]`; }); }
document.getElementById('jadd').onclick=jrow; jrow(); jrow();
</script>
@endsection
""")
for rep, title, cols in [
 ("ledger","Buku Besar","Tanggal|Nomor|Akun|Debit|Kredit"),
 ("trial","Neraca Saldo","Kode|Akun|Debit|Kredit"),
 ("pl","Laba Rugi",""),
 ("aging","Aging AR/AP","Nama|Nomor|Jatuh Tempo|Sisa|Overdue"),
 ("closing","Tutup Periode","Periode|Mulai|Selesai|Status"),
]:
    if rep=="ledger":
        body = """<form method="GET" class="row g-2 mb-2"><div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div><div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div><div class="col-md-4"><select name="coa_id" class="form-select"><option value="">Semua akun</option>@foreach($coas as $c)<option value="{{ $c->id }}" @selected(request('coa_id')==$c->id)>{{ $c->code }} — {{ $c->name }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Nomor</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ tgl_id($r->date) }}</td><td>{{ $r->number }}</td><td>{{ $r->code }} — {{ $r->name }}</td><td class="text-end">{{ idr($r->debit) }}</td><td class="text-end">{{ idr($r->credit) }}</td></tr>@endforeach</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div>"""
    elif rep=="trial":
        body = """<div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ $r->code }}</td><td>{{ $r->name }}</td><td class="text-end">{{ idr($r->debit) }}</td><td class="text-end">{{ idr($r->credit) }}</td></tr>@endforeach</tbody></table></div>"""
    elif rep=="pl":
        body = """<div class="row"><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Pendapatan</div><div class="h2">{{ idr($revenue) }}</div></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Beban</div><div class="h2">{{ idr($expense) }}</div></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Laba Bersih</div><div class="h2">{{ idr($net) }}</div></div></div></div></div>
<div class="table-responsive mt-2"><table class="table"><thead><tr><th>Kode</th><th>Akun</th><th>Tipe</th><th class="text-end">Mutasi</th></tr></thead><tbody>
@foreach($detail as $d)<tr><td>{{ $d->code }}</td><td>{{ $d->name }}</td><td>{{ $d->type }}</td><td class="text-end">{{ idr($d->c - $d->d) }}</td></tr>@endforeach</tbody></table></div>"""
    elif rep=="aging":
        body = """<div class="mb-2"><a href="?kind=ar" class="btn btn-sm btn-outline-primary">Piutang</a> <a href="?kind=ap" class="btn btn-sm btn-outline-primary">Utang</a></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nama</th><th>Nomor</th><th>Jatuh Tempo</th><th class="text-end">Sisa</th><th class="text-end">Overdue (hari)</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->number }}</td><td>{{ $r->due_date }}</td><td class="text-end">{{ idr($r->sisa) }}</td><td class="text-end">{{ $r->overdue }}</td></tr>@endforeach</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div>"""
    else:
        body = """<div class="table-responsive"><table class="table"><thead><tr><th>Periode</th><th>Mulai</th><th>Selesai</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($periods as $p)<tr><td>{{ $p->name }}</td><td>{{ $p->start_date }}</td><td>{{ $p->end_date }}</td><td>{{ $p->is_closed?'Tutup':'Buka' }}</td>
<td>@if(!$p->is_closed)<form method="POST" action="{{ route('finance.closing.close') }}" class="d-inline">@csrf<input type="hidden" name="period_id" value="{{ $p->id }}"><button class="btn btn-sm btn-warning">Tutup</button></form>
@else<form method="POST" action="{{ route('finance.closing.reopen') }}" class="d-inline">@csrf<input type="hidden" name="period_id" value="{{ $p->id }}"><input type="hidden" name="reason" value="koreksi terotorisasi"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Buka kembali periode? Tercatat + butuh otorisasi.')">Buka Kembali</button></form>@endif</td></tr>@endforeach</tbody></table></div>"""
    w(f"resources/views/modules/finance/reports/{rep}.blade.php", """@extends('layouts.tabler')
@section('title','""" + title + """')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">""" + title + """</h3></div><div class="card-body">
""" + body + """
</div></div>
@endsection
""")

# ---------- HR views ----------
w("resources/views/modules/hr/attendance/index.blade.php", """@extends('layouts.tabler')
@section('title','Absensi')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Absensi</h3></div><div class="card-body">
<form method="POST" action="{{ route('hr.attendance.store') }}">@csrf
<div class="row"><div class="mb-2 col-md-4"><label class="form-label">ID Karyawan</label><input type="number" name="employee_id" class="form-control" required></div>
<div class="mb-2 col-md-3"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-2 col-md-2"><label class="form-label">Masuk</label><input type="time" name="check_in" class="form-control"></div>
<div class="mb-2 col-md-2"><label class="form-label">Keluar</label><input type="time" name="check_out" class="form-control"></div>
<div class="mb-2 col-md-1"><label class="form-label">&nbsp;</label><button class="btn btn-primary d-block">OK</button></div></div></form></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Karyawan</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr></thead>
<tbody>@foreach($rows as $r)<tr><td>{{ $r->date }}</td><td>{{ $r->name }}</td><td>{{ $r->check_in }}</td><td>{{ $r->check_out }}</td><td>{{ $r->status }}</td></tr>@endforeach</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/hr/payroll/index.blade.php", """@extends('layouts.tabler')
@section('title','Payroll')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Payroll</h2></div>
<div class="col-auto"><a href="{{ route('hr.payroll.create') }}" class="btn btn-primary">+ Hitung Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td>{{ $r->status }}</td>
<td><form method="POST" action="{{ route('hr.payroll.approve',$r->id) }}">@csrf<button class="btn btn-sm btn-primary">Setujui</button></form></td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada payroll.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
w("resources/views/modules/hr/payroll/form.blade.php", """@extends('layouts.tabler')
@section('title','Payroll Baru')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Hitung Payroll (komponen configurable)</h3></div><div class="card-body">
<p class="text-muted">PENDING_BUSINESS_DECISION: tarif pajak/BPJS/lembur mengikuti konfigurasi payroll_components yang disetujui HR/Finance, bukan aturan hardcode.</p>
<form method="POST" action="{{ route('hr.payroll.store') }}">@csrf
<div class="mb-3 col-md-4"><label class="form-label">Periode</label><select name="payroll_period_id" class="form-select">@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<table class="table" id="ptab"><thead><tr><th>Karyawan</th><th>Bruto</th><th>Potongan</th><th></th></tr></thead><tbody></tbody></table>
<button type="button" class="btn btn-outline-primary" id="padd">+ Baris</button>
<button class="btn btn-primary">Simpan</button></form></div></div>
<script>
const emps = @json($employees->map(fn($e)=>['id'=>$e->id,'name'=>$e->name]));
const tb = document.querySelector('#ptab tbody');
function prow(){
const tr = document.createElement('tr');
tr.innerHTML = `<td><select class="form-select" data-n="employee_id">${emps.map(e=>`<option value="${e.id}">${e.name}</option>`).join('')}</select></td>
<td><input type="number" step="any" value="0" class="form-control" data-n="gross"></td>
<td><input type="number" step="any" value="0" class="form-control" data-n="deduction"></td>
<td><button type="button" class="btn btn-sm rm">×</button></td>`;
tr.querySelector('.rm').onclick=()=>tr.remove(); tb.appendChild(tr); ren();
}
function ren(){ [...tb.rows].forEach((tr,i)=>{ ['employee_id','gross','deduction'].forEach(n=>{ tr.querySelector(`[data-n=${n}]`).name=`items[${i}][${n}]`; }); }); }
document.getElementById('padd').onclick=prow; prow();
</script>
@endsection
""")

# ---------- approvals / reports / import ----------
w("resources/views/modules/gov/approvals/index.blade.php", """@extends('layouts.tabler')
@section('title','Approval')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Antrean Persetujuan</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Dokumen</th><th>ID</th><th>Level</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $a)<tr><td>{{ $a->doc_type }}</td><td>{{ $a->doc_id }}</td><td>{{ $a->level }}</td><td><span class="badge bg-yellow">{{ $a->status }}</span></td>
<td>@if($a->status==='pending')<form method="POST" action="{{ route('gov.approvals.decide',$a->id) }}" class="d-inline">@csrf
<input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-primary">Setujui</button></form>
<form method="POST" action="{{ route('gov.approvals.decide',$a->id) }}" class="d-inline">@csrf
<input type="hidden" name="decision" value="rejected"><button class="btn btn-sm btn-outline-danger">Tolak</button></form>@endif</td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Tidak ada antrean.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
for rep, title in [("sales","Laporan Penjualan"),("inventory","Laporan Inventori"),("purchase","Laporan Pembelian")]:
    w(f"resources/views/modules/reports/{rep}.blade.php", """@extends('layouts.tabler')
@section('title','""" + title + """')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">""" + title + """</h3></div>
<div class="col-auto"><a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-outline-primary">Export CSV</a></div></div></div>
<div class="card-body"><form method="GET" class="row g-2 mb-2"><div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div>
<div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form>
<div class="table-responsive"><table class="table"><thead><tr>""" +
(" <th>Nomor</th><th>Tanggal</th><th>Pelanggan</th><th class='text-end'>Total</th><th>Status</th>" if rep=="sales" else ("<th>Waktu</th><th>SKU</th><th>Produk</th><th class='text-end'>Qty</th><th>Ref</th>" if rep=="inventory" else "<th>Nomor</th><th>Supplier</th><th class='text-end'>Total</th><th>Status</th>")) + """</tr></thead><tbody>
@foreach($rows as $r)<tr>""" +
(" <td>{{ $r->number }}</td><td>{{ $r->date }}</td><td>{{ $r->customer ?? $r->name }}</td><td class='text-end'>{{ idr($r->grand_total) }}</td><td>{{ $r->pay_status ?? $r->status }}</td>" if rep in ("sales","purchase") else "<td>{{ $r->created_at }}</td><td>{{ $r->sku }}</td><td>{{ $r->product }}</td><td class='text-end'>{{ $r->qty }}</td><td>{{ $r->ref_type }} #{{ $r->ref_id }}</td>") + """</tr>@endforeach</tbody></table></div></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection
""")
for imp, title in [("customers","Import Pelanggan"),("products","Import Produk")]:
    w(f"resources/views/modules/import/{imp}.blade.php", """@extends('layouts.tabler')
@section('title','""" + title + """')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">""" + title + """ (CSV, maks 2MB, ada preview validasi)</h3></div>
<div class="card-body">
<form method="POST" enctype="multipart/form-data">@csrf
<input type="file" name="file" class="form-control" accept=".csv,.txt" required>
<button class="btn btn-primary mt-2">Upload & Validasi</button></form>
@if(session('import_errors'))<div class="alert alert-warning mt-3"><strong>Catatan validasi:</strong><ul class="mb-0">@foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<p class="text-muted mt-2">Format """ + imp + """ header: """ + ("code,name,phone,email" if imp=="customers" else "sku,name,sell_price,cost_price") + """</p>
</div></div>
@endsection
""")

# ---------- print ----------
w("resources/views/print/invoice.blade.php", """<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><style>body{font-family:sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #999;padding:6px;text-align:left}.r{text-align:right}</style></head>
<body>
<h2>INVOICE {{ $row->number }}</h2>
<p>Tanggal: {{ tgl_id($row->date) }} • Jatuh tempo: {{ tgl_id($row->due_date) }}<br>Pelanggan: {{ $row->customer->name ?? '-' }}</p>
<table><thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Harga</th><th class="r">Total</th></tr></thead>
<tbody>@foreach($row->lines as $l)<tr><td>{{ $l->snapshot_name }}</td><td class="r">{{ $l->qty }}</td><td class="r">{{ idr($l->price) }}</td><td class="r">{{ idr($l->line_total) }}</td></tr>@endforeach</tbody>
<tfoot><tr><th colspan="3" class="r">Grand Total</th><th class="r">{{ idr($row->grand_total) }}</th></tr></tfoot></table>
<p>Terima kasih atas kepercayaan Anda.</p>
</body></html>
""")
w("resources/views/print/payslip.blade.php", """<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><style>body{font-family:sans-serif;font-size:12px}</style></head>
<body>
<h2>SLIP GAJI {{ $slip->number }}</h2>
<p>Nama: {{ $slip->name }} ({{ $slip->nip }})<br>Gaji bersih: <strong>{{ idr($slip->net) }}</strong></p>
<p><em>Dokumen rahasia — hanya untuk karyawan bersangkutan dan pihak berwenang.</em></p>
</body></html>
""")

# ---------- errors ----------
for code, msg in [("403","Akses Ditolak"),("404","Halaman Tidak Ditemukan"),("500","Kesalahan Server")]:
    w(f"resources/views/errors/{code}.blade.php", """@extends('layouts.tabler')
@section('title','""" + msg + """')
@section('content')
<div class="empty-state"><h1>""" + code + """</h1><p>""" + msg + """ — silakan kembali ke <a href="{{ route('dashboard') }}">dashboard</a>.</p></div>
@endsection
""")

# ---------- PWA ----------
w("public/manifest.webmanifest", """{
  "name": "KazaERP - Kazaka Group",
  "short_name": "KazaERP",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#206bc4",
  "icons": [{"src": "/icon-192.png", "sizes": "192x192", "type": "image/png"}, {"src": "/icon-512.png", "sizes": "512x512", "type": "image/png"}]
}
""")
w("public/sw.js", """// KazaERP service worker: cache aset statis berversi saja. JANGAN cache halaman sensitif/API.
const CACHE = 'kaza-v1';
const ASSETS = [];
self.addEventListener('install', (e) => { self.skipWaiting(); });
self.addEventListener('activate', (e) => { e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k))))); });
self.addEventListener('fetch', (e) => {
  const u = new URL(e.request.url);
  if (e.request.method !== 'GET') return;
  if (u.pathname.startsWith('/build/')) {
    e.respondWith(caches.open(CACHE).then((c) => c.match(e.request).then((m) => m || fetch(e.request).then((r) => { c.put(e.request, r.clone()); return r; }))));
  }
});
""")
print("gen10 done")

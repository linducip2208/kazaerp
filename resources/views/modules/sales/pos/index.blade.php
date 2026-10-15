@extends('layouts.tabler')
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

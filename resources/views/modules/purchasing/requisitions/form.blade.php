@extends('layouts.tabler')
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

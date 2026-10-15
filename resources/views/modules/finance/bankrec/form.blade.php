@extends('layouts.tabler')
@section('title','Rekonsiliasi Baru')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Rekonsiliasi Baru</h3></div><div class="card-body">
<form method="POST" action="{{ route('finance.bankrec.store') }}">@csrf
<div class="row">
<div class="mb-3 col-md-4"><label class="form-label">Rekening</label><select name="bank_account_id" class="form-select">@foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->bank_name }} — {{ $b->account_no }}</option>@endforeach</select></div>
<div class="mb-3 col-md-2"><label class="form-label">Periode</label><input type="date" name="period" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-3"><label class="form-label">Saldo buku</label><input type="number" step="any" name="book_balance" class="form-control" required></div>
<div class="mb-3 col-md-3"><label class="form-label">Saldo bank</label><input type="number" step="any" name="bank_balance" class="form-control" required></div>
<div class="mb-3 col-md-12"><label class="form-label">Catatan</label><input name="note" class="form-control"></div>
</div>
<button class="btn btn-primary">Simpan</button></form></div></div>
@endsection

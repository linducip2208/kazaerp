@extends('layouts.tabler')
@section('title','Laba Rugi')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Laba Rugi</h3></div><div class="col-auto"><a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-sm btn-outline-primary">Ekspor CSV</a> <button class="btn btn-sm btn-secondary" onclick="window.print()">Cetak</button></div></div></div><div class="card-body">
<div class="row"><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Pendapatan</div><div class="h2">{{ idr($revenue) }}</div></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Beban</div><div class="h2">{{ idr($expense) }}</div></div></div></div>
<div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Laba Bersih</div><div class="h2">{{ idr($net) }}</div></div></div></div></div>
<div class="table-responsive mt-2"><table class="table"><thead><tr><th>Kode</th><th>Akun</th><th>Tipe</th><th class="text-end">Mutasi</th></tr></thead><tbody>
@foreach($detail as $d)<tr><td>{{ $d->code }}</td><td>{{ $d->name }}</td><td>{{ $d->type }}</td><td class="text-end">{{ idr($d->c - $d->d) }}</td></tr>@endforeach</tbody></table></div>
</div></div>
@endsection

@extends('layouts.tabler')
@section('title','Neraca Saldo')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Neraca Saldo</h3></div><div class="col-auto"><a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-sm btn-outline-primary">Ekspor CSV</a> <button class="btn btn-sm btn-secondary" onclick="window.print()">Cetak</button></div></div></div><div class="card-body">
<div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ $r->code }}</td><td>{{ $r->name }}</td><td class="text-end">{{ idr($r->debit) }}</td><td class="text-end">{{ idr($r->credit) }}</td></tr>@endforeach</tbody></table></div>
</div></div>
@endsection

@extends('layouts.tabler')
@section('title','Buku Besar')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Buku Besar</h3></div><div class="card-body">
<form method="GET" class="row g-2 mb-2"><div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ request('from') }}"></div><div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ request('to') }}"></div><div class="col-md-4"><select name="coa_id" class="form-select"><option value="">Semua akun</option>@foreach($coas as $c)<option value="{{ $c->id }}" @selected(request('coa_id')==$c->id)>{{ $c->code }} — {{ $c->name }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-secondary">Filter</button></div></form>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Nomor</th><th>Akun</th><th class="text-end">Debit</th><th class="text-end">Kredit</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ tgl_id($r->date) }}</td><td>{{ $r->number }}</td><td>{{ $r->code }} — {{ $r->name }}</td><td class="text-end">{{ idr($r->debit) }}</td><td class="text-end">{{ idr($r->credit) }}</td></tr>@endforeach</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div>
</div></div>
@endsection

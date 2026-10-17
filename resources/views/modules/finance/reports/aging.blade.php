@extends('layouts.tabler')
@section('title','Aging AR/AP')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Aging AR/AP</h3></div><div class="col-auto"><a href="?export=csv&{{ http_build_query(request()->except('export')) }}" class="btn btn-sm btn-outline-primary">Ekspor CSV</a></div></div></div><div class="card-body">
<div class="mb-2"><a href="?kind=ar" class="btn btn-sm btn-outline-primary">Piutang</a> <a href="?kind=ap" class="btn btn-sm btn-outline-primary">Utang</a></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nama</th><th>Nomor</th><th>Jatuh Tempo</th><th class="text-end">Sisa</th><th class="text-end">Overdue (hari)</th></tr></thead><tbody>
@foreach($rows as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->number }}</td><td>{{ $r->due_date ? tgl_id($r->due_date) : '—' }}</td><td class="text-end">{{ idr($r->sisa) }}</td><td class="text-end">{{ $r->overdue }}</td></tr>@endforeach</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div>
</div></div>
@endsection

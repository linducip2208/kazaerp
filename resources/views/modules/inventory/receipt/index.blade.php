@extends('layouts.tabler')
@section('title','Penerimaan Barang')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Penerimaan (mendukung partial)</h2></div>
<div class="col-auto"><a href="{{ route('inventory.receipts.create') }}" class="btn btn-primary">+ Terima</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Ref</th><th>Status</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td>{{ $r->ref_type }} #{{ $r->ref_id }}</td><td>{{ $r->status }}</td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada penerimaan.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

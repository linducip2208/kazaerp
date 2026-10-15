@extends('layouts.tabler')
@section('title','Permintaan Pembelian')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Permintaan Pembelian</h2></div>
<div class="col-auto"><a href="{{ route('purchasing.requisitions.create') }}" class="btn btn-primary">+ Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Status</th><th>Darurat</th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td>{{ tgl_id($r->date) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td><td>{{ $r->is_emergency?'Ya':'Tidak' }}</td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada data.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

@extends('layouts.tabler')
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

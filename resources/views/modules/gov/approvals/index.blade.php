@extends('layouts.tabler')
@section('title','Approval')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Antrean Persetujuan</h3></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Dokumen</th><th>ID</th><th>Level</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $a)<tr><td>{{ $a->doc_type }}</td><td>{{ $a->doc_id }}</td><td>{{ $a->level }}</td><td><span class="badge bg-{{ $a->status==='approved' ? 'green' : ($a->status==='rejected' ? 'red' : ($a->status==='pending' ? 'yellow' : 'secondary')) }}">{{ $a->status }}</span></td>
<td>@if($a->status==='pending')<form method="POST" action="{{ route('gov.approvals.decide',$a->id) }}" class="d-inline">@csrf
<input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-primary">Setujui</button></form>
<form method="POST" action="{{ route('gov.approvals.decide',$a->id) }}" class="d-inline">@csrf
<input type="hidden" name="decision" value="rejected"><button class="btn btn-sm btn-outline-danger">Tolak</button></form>@endif</td></tr>
@empty<tr><td colspan="5"><div class="empty-state">Tidak ada antrean.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

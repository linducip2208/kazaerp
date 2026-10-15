@extends('layouts.tabler')
@section('title','Payroll')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Payroll</h2></div>
<div class="col-auto"><a href="{{ route('hr.payroll.create') }}" class="btn btn-primary">+ Hitung Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Nomor</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->number }}</td><td class="text-end">{{ idr($r->grand_total) }}</td><td>{{ $r->status }}</td>
<td><form method="POST" action="{{ route('hr.payroll.approve',$r->id) }}">@csrf<button class="btn btn-sm btn-primary">Setujui</button></form></td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada payroll.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

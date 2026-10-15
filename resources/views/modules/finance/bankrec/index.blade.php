@extends('layouts.tabler')
@section('title','Rekonsiliasi Bank')
@section('content')
<div class="page-header"><div class="row align-items-center"><div class="col"><h2 class="page-title">Rekonsiliasi Bank</h2></div>
<div class="col-auto"><a href="{{ route('finance.bankrec.create') }}" class="btn btn-primary">+ Baru</a></div></div></div>
<div class="card mt-2"><div class="table-responsive"><table class="table"><thead><tr><th>Bank</th><th>Periode</th><th class="text-end">Buku</th><th class="text-end">Bank</th><th class="text-end">Selisih</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $r)<tr><td>{{ $r->bank_name }} {{ $r->account_no }}</td><td>{{ $r->period }}</td><td class="text-end">{{ idr($r->book_balance) }}</td><td class="text-end">{{ idr($r->bank_balance) }}</td><td class="text-end">{{ idr($r->difference) }}</td><td><span class="badge bg-blue">{{ $r->status }}</span></td>
<td>@if($r->status!=='confirmed')<form method="POST" action="{{ route('finance.bankrec.confirm',$r->id) }}">@csrf<button class="btn btn-sm btn-primary">Konfirmasi</button></form>@endif</td></tr>
@empty<tr><td colspan="7"><div class="empty-state">Belum ada rekonsiliasi.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

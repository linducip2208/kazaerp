@extends('layouts.tabler')
@section('title','Tutup Periode')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Tutup Periode</h3></div><div class="card-body">
<div class="table-responsive"><table class="table"><thead><tr><th>Periode</th><th>Mulai</th><th>Selesai</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($periods as $p)<tr><td>{{ $p->name }}</td><td>{{ $p->start_date }}</td><td>{{ $p->end_date }}</td><td>{{ $p->is_closed?'Tutup':'Buka' }}</td>
<td>@if(!$p->is_closed)<form method="POST" action="{{ route('finance.closing.close') }}" class="d-inline">@csrf<input type="hidden" name="period_id" value="{{ $p->id }}"><button class="btn btn-sm btn-warning">Tutup</button></form>
@else<form method="POST" action="{{ route('finance.closing.reopen') }}" class="d-inline">@csrf<input type="hidden" name="period_id" value="{{ $p->id }}"><input type="hidden" name="reason" value="koreksi terotorisasi"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Buka kembali periode? Tercatat + butuh otorisasi.')">Buka Kembali</button></form>@endif</td></tr>@endforeach</tbody></table></div>
</div></div>
@endsection

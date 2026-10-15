@extends('layouts.tabler')
@section('title','Notifikasi')
@section('content')
<div class="card"><div class="card-header"><div class="row align-items-center"><div class="col"><h3 class="card-title">Notifikasi Saya</h3></div>
<div class="col-auto"><form method="POST" action="{{ route('notifications.readAll') }}">@csrf<button class="btn btn-sm btn-outline-primary">Tandai semua dibaca</button></form></div></div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Isi</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($rows as $n)<tr><td>{{ tgl_id($n->created_at,true) }}</td><td>{{ $n->title }}</td><td>{{ $n->read_at?'Dibaca':'Baru' }}</td>
<td><a href="{{ route('notifications.read',$n->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Tidak ada notifikasi.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

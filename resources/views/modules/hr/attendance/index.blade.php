@extends('layouts.tabler')
@section('title','Absensi')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Absensi</h3></div><div class="card-body">
<form method="POST" action="{{ route('hr.attendance.store') }}">@csrf
<div class="row"><div class="mb-2 col-md-4"><label class="form-label">ID Karyawan</label><input type="number" name="employee_id" class="form-control" required></div>
<div class="mb-2 col-md-3"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-2 col-md-2"><label class="form-label">Masuk</label><input type="time" name="check_in" class="form-control"></div>
<div class="mb-2 col-md-2"><label class="form-label">Keluar</label><input type="time" name="check_out" class="form-control"></div>
<div class="mb-2 col-md-1"><label class="form-label">&nbsp;</label><button class="btn btn-primary d-block">OK</button></div></div></form></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Karyawan</th><th>Masuk</th><th>Keluar</th><th>Status</th></tr></thead>
<tbody>@foreach($rows as $r)<tr><td>{{ $r->date }}</td><td>{{ $r->name }}</td><td>{{ $r->check_in }}</td><td>{{ $r->check_out }}</td><td>{{ $r->status }}</td></tr>@endforeach</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

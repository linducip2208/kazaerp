@extends('layouts.tabler')
@section('title','Berkas Privat')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Berkas Privat (akses terotorisasi + tercatat)</h3></div>
<div class="card-body">
<form method="POST" action="{{ route('files.store') }}" enctype="multipart/form-data">@csrf
<div class="row">
<div class="mb-2 col-md-3"><label class="form-label">Tipe pemilik</label><input name="owner_type" class="form-control" placeholder="Customer / StudioProject / ..." required></div>
<div class="mb-2 col-md-2"><label class="form-label">ID pemilik</label><input type="number" name="owner_id" class="form-control" required></div>
<div class="mb-2 col-md-4"><label class="form-label">Berkas (maks 10MB: pdf/jpg/png/csv/xls/doc/zip)</label><input type="file" name="file" class="form-control" required></div>
<div class="mb-2 col-md-3"><label class="form-label">&nbsp;</label><button class="btn btn-primary d-block">Unggah Privat</button></div>
</div></form>
</div>
<div class="table-responsive"><table class="table"><thead><tr><th>Nama</th><th>Pemilik</th><th>Ukuran</th><th></th></tr></thead>
<tbody>@forelse($rows as $a)<tr><td>{{ $a->file_name }}</td><td>{{ $a->owner_type }} #{{ $a->owner_id }}</td><td>{{ number_format($a->size) }} B</td>
<td><a href="{{ route('files.download',$a->id) }}" class="btn btn-sm btn-outline-primary">Unduh</a></td></tr>
@empty<tr><td colspan="4"><div class="empty-state">Belum ada berkas.</div></td></tr>@endforelse</tbody></table></div>
<div class="card-footer">{{ $rows->links() }}</div></div>
@endsection

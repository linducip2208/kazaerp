@extends('layouts.tabler')
@section('title', $meta['title'])
@section('content')
@include('components.crud-show', ['row'=>$row,'meta'=>$meta])
<div class="card mt-3"><div class="card-header"><h3 class="card-title">Penerima kampanye (CRM-06)</h3>
<div class="card-subtitle">Pelanggan aktif, tanpa opt-out, tanpa data gabungan. {{ $excludedOptOut }} pelanggan dikecualikan (opt-out).</div></div>
<div class="table-responsive"><table class="table"><thead><tr><th>Kode</th><th>Nama</th><th>Telepon</th></tr></thead>
<tbody>@forelse($recipients as $c)<tr><td>{{ $c->code }}</td><td>{{ $c->name }}</td><td>{{ $c->phone ?? '-' }}</td></tr>
@empty<tr><td colspan="3"><div class="empty-state">Tidak ada penerima yang memenuhi syarat.</div></td></tr>@endforelse</tbody></table></div>
@if(method_exists($recipients,'links'))<div class="card-footer">{{ $recipients->links() }}</div>@endif
</div>
@endsection

{{-- Peringatan duplikat (read-only, advisory): tidak menghapus/menggabung apa pun. --}}
@if(isset($dupes) && count($dupes))
<div class="alert alert-warning mt-3" role="alert">
<strong>Kemungkinan duplikat ({{ count($dupes) }}):</strong>
tinjau sebelum membuat data baru yang sama. Contoh: telepon/nama identik dalam entitas yang sama.
<ul class="mb-0">
@foreach($dupes as $d)
<li><a href="{{ route($meta['route'].'.show', $d->id) }}">{{ $d->code ?? $d->sku ?? ('#'.$d->id) }} — {{ $d->name }}</a></li>
@endforeach
</ul>
</div>
@endif

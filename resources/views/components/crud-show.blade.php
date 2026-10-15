<div class="card"><div class="card-header"><div class="row align-items-center">
<div class="col"><h3 class="card-title">{{ $meta['title'] }} #{{ $row->number ?? $row->code ?? $row->id }}</h3></div>
<div class="col-auto no-print">
<a href="{{ route($meta['route'].'.edit',$row->id) }}" class="btn btn-outline-primary">Ubah</a>
<form method="POST" action="{{ route($meta['route'].'.destroy',$row->id) }}" class="d-inline" onsubmit="return confirm('Hapus data ini?')">@csrf @method('DELETE')<button class="btn btn-outline-danger">Hapus</button></form>
</div></div></div>
<div class="card-body"><dl class="row">
@foreach($meta['columns'] as $col=>$label)<dt class="col-sm-3">{{ $label }}</dt><dd class="col-sm-9">{{ $row->$col ?? '-' }}</dd>@endforeach
</dl>{{ $slot ?? '' }}</div></div>

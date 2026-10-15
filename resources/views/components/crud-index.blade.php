@php $m = $meta; @endphp
<div class="page-header d-print-none"><div class="row align-items-center">
<div class="col"><h2 class="page-title">{{ $m['title'] }}</h2></div>
<div class="col-auto"><a href="{{ route($m['route'].'.create') }}" class="btn btn-primary no-print">+ Tambah</a></div>
</div></div>
<div class="card mt-2"><div class="card-body border-bottom">
<form method="GET" class="row g-2">
<div class="col-md-4"><input name="q" class="form-control" placeholder="Cari…" value="{{ request('q') }}"></div>
<div class="col-md-3"><select name="status" class="form-select" onchange="this.form.submit()"><option value="">— Semua status —</option>
@foreach(['draft','submitted','pending','open','approved','posted','paid','active','scheduled','reserved'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>@endforeach</select></div>
<div class="col-md-2"><button class="btn btn-secondary">Filter</button></div>
</form></div>
<div class="table-responsive"><table class="table table-vcenter">
<thead><tr>@foreach($m['columns'] as $label)<th>{{ $label }}</th>@endforeach<th></th></tr></thead>
<tbody>
@forelse($rows as $row)<tr>
@foreach(array_keys($m['columns']) as $col)
<td>@if(str_contains($col,'price')||str_contains($col,'amount')||str_contains($col,'total')){{ idr($row->$col ?? 0) }}@elseif($col==='status'||$col==='pay_status')<span class="badge bg-blue">{{ $row->$col }}</span>@elseif($col==='is_active'){{ ($row->$col)?'Ya':'Tidak' }}@else{{ Str::limit($row->$col ?? '-',60) }}@endif</td>
@endforeach
<td class="text-end"><a href="{{ route($m['route'].'.show',$row->id) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
</tr>
@empty<tr><td colspan="{{ count($m['columns'])+1 }}"><div class="empty-state">Belum ada data. Klik <strong>+ Tambah</strong> untuk membuat pertama.</div></td></tr>
@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="card-footer">{{ $rows->links() }}</div>@endif
</div>

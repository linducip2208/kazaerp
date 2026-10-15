<div class="card"><div class="card-header"><h3 class="card-title">{{ $meta['title'] }}</h3></div>
<div class="card-body">
<form method="POST" action="{{ $action }}">@csrf @if($method!=='POST')@method($method)@endif
<div class="row">
@foreach($meta['fields'] as $f)
<div class="mb-3 col-md-6">
<label class="form-label">{{ $f['label'] }}</label>
@if($f['type']==='textarea')
<textarea name="{{ $f['name'] }}" class="form-control">{{ old($f['name'], $row->{$f['name']} ?? '') }}</textarea>
@elseif($f['type']==='select' && isset($f['options']))
<select name="{{ $f['name'] }}" class="form-select">@foreach($f['options'] as $v=>$l)<option value="{{ $v }}" @selected((string)old($f['name'], $row->{$f['name']} ?? '')===(string)$v)>{{ $l }}</option>@endforeach</select>
@elseif($f['type']==='select_model')
@php $mm=$f['model']; $ol=$f['option_label']??'name'; $opts=$mm::orderBy('id')->limit(300)->get(); @endphp
<select name="{{ $f['name'] }}" class="form-select"><option value="">— Pilih —</option>@foreach($opts as $o)<option value="{{ $o->id }}" @selected((string)old($f['name'], $row->{$f['name']} ?? '')===(string)$o->id)>{{ $o->$ol ?? $o->id }}</option>@endforeach</select>
@elseif($f['type']==='date')
<input type="date" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], isset($row->{$f['name']})&&$row->{$f['name']} ? substr((string)$row->{$f['name']},0,10) : '') }}">
@elseif($f['type']==='number')
<input type="number" step="any" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], $row->{$f['name']} ?? '') }}">
@elseif($f['type']==='password')
<input type="password" name="{{ $f['name'] }}" class="form-control" autocomplete="new-password">
@elseif($f['type']==='email')
<input type="email" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], $row->{$f['name']} ?? '') }}">
@else
<input type="text" name="{{ $f['name'] }}" class="form-control" value="{{ old($f['name'], $row->{$f['name']} ?? '') }}">
@endif
</div>
@endforeach
</div>
<div class="mt-2"><button class="btn btn-primary">Simpan</button>
<a href="{{ route($meta['route'].'.index') }}" class="btn btn-link">Batal</a></div>
</form></div></div>

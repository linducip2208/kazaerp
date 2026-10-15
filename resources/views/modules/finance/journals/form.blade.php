@extends('layouts.tabler')
@section('title','Jurnal Baru')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Jurnal (wajib balance)</h3></div><div class="card-body">
<form method="POST" action="{{ route('finance.journals.store') }}">@csrf
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-8"><label class="form-label">Deskripsi</label><input name="description" class="form-control" required></div></div>
<table class="table" id="jtab"><thead><tr><th>Akun</th><th>Debit</th><th>Kredit</th><th></th></tr></thead><tbody></tbody></table>
<button type="button" class="btn btn-outline-primary" id="jadd">+ Baris</button>
<button class="btn btn-primary">Posting</button></form></div></div>
<script>
const coas = @json($coas->map(fn($c)=>['id'=>$c->id,'label'=>$c->code.' — '.$c->name]));
const tb = document.querySelector('#jtab tbody');
function jrow(){
const tr = document.createElement('tr');
tr.innerHTML = `<td><select class="form-select" data-n="coa_id">${coas.map(c=>`<option value="${c.id}">${c.label}</option>`).join('')}</select></td>
<td><input type="number" step="any" min="0" value="0" class="form-control" data-n="debit"></td>
<td><input type="number" step="any" min="0" value="0" class="form-control" data-n="credit"></td>
<td><button type="button" class="btn btn-sm rm">×</button></td>`;
tr.querySelector('.rm').onclick=()=>tr.remove(); tb.appendChild(tr); renumber();
}
function renumber(){ [...tb.rows].forEach((tr,i)=>{ tr.querySelector('[data-n=coa_id]').name=`lines[${i}][coa_id]`; tr.querySelector('[data-n=debit]').name=`lines[${i}][debit]`; tr.querySelector('[data-n=credit]').name=`lines[${i}][credit]`; }); }
document.getElementById('jadd').onclick=jrow; jrow(); jrow();
</script>
@endsection

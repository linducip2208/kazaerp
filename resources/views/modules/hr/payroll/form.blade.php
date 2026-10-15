@extends('layouts.tabler')
@section('title','Payroll Baru')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Hitung Payroll (komponen configurable)</h3></div><div class="card-body">
<p class="text-muted">PENDING_BUSINESS_DECISION: tarif pajak/BPJS/lembur mengikuti konfigurasi payroll_components yang disetujui HR/Finance, bukan aturan hardcode.</p>
<form method="POST" action="{{ route('hr.payroll.store') }}">@csrf
<div class="mb-3 col-md-4"><label class="form-label">Periode</label><select name="payroll_period_id" class="form-select">@foreach($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
<table class="table" id="ptab"><thead><tr><th>Karyawan</th><th>Bruto</th><th>Potongan</th><th></th></tr></thead><tbody></tbody></table>
<button type="button" class="btn btn-outline-primary" id="padd">+ Baris</button>
<button class="btn btn-primary">Simpan</button></form></div></div>
<script>
const emps = @json($employees->map(fn($e)=>['id'=>$e->id,'name'=>$e->name]));
const tb = document.querySelector('#ptab tbody');
function prow(){
const tr = document.createElement('tr');
tr.innerHTML = `<td><select class="form-select" data-n="employee_id">${emps.map(e=>`<option value="${e.id}">${e.name}</option>`).join('')}</select></td>
<td><input type="number" step="any" value="0" class="form-control" data-n="gross"></td>
<td><input type="number" step="any" value="0" class="form-control" data-n="deduction"></td>
<td><button type="button" class="btn btn-sm rm">×</button></td>`;
tr.querySelector('.rm').onclick=()=>tr.remove(); tb.appendChild(tr); ren();
}
function ren(){ [...tb.rows].forEach((tr,i)=>{ ['employee_id','gross','deduction'].forEach(n=>{ tr.querySelector(`[data-n=${n}]`).name=`items[${i}][${n}]`; }); }); }
document.getElementById('padd').onclick=prow; prow();
</script>
@endsection

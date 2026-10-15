@extends('layouts.tabler')
@section('title','Penerimaan Barang')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Penerimaan Baru</h3></div><div class="card-body">
<form method="POST" action="{{ route('inventory.receipts.store') }}">@csrf
<div class="row"><div class="mb-3 col-md-4"><label class="form-label">Gudang</label><select name="warehouse_id" class="form-select">@foreach($warehouses as $x)<option value="{{ $x->id }}">{{ $x->code }} — {{ $x->name }}</option>@endforeach</select></div>
<div class="mb-3 col-md-4"><label class="form-label">Tanggal</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}"></div>
<div class="mb-3 col-md-4"><label class="form-label">PO (opsional, untuk partial)</label><select name="purchase_order_id" class="form-select"><option value="">— Tanpa PO —</option>@foreach($pos as $p)<option value="{{ $p->id }}">{{ $p->number }}</option>@endforeach</select></div></div>

<table class="table" id="lines-table"><thead><tr>
<th>Produk</th><th style="width:100px">Qty</th><th style="width:130px">Harga</th>
<th style="width:140px">No. Lot</th><th style="width:150px">Kedaluwarsa</th><th style="width:150px">QC Awal</th><th></th>
</tr></thead><tbody></tbody></table>
<button type="button" class="btn btn-outline-primary" id="add-line">+ Baris</button>
<div class="form-hint mt-2">Isi <strong>No. Lot</strong> untuk membuat batch otomatis. Batch dengan QC <em>pending/quarantine/rejected</em> tidak dapat dikeluarkan dari gudang sampai di-Release pada menu <a href="{{ route('inventory.batches.index') }}">Batch & QC</a>.</div>
<button class="btn btn-primary mt-2">Simpan ke Ledger</button></form></div></div>
<script>
(function(){
  const prods = @json($products->map(fn($p)=>['id'=>$p->id,'name'=>$p->name])->values());
  const tb = document.querySelector('#lines-table tbody');
  function addRow(){
    const tr = document.createElement('tr');
    const opts = prods.map(p=>`<option value="${p.id}">${p.name}</option>`).join('');
    tr.innerHTML = `<td><select class="form-select prod">${opts}</select></td>
      <td><input type="number" step="any" min="0.0001" value="1" class="form-control qty"></td>
      <td><input type="number" step="any" min="0" value="0" class="form-control cost"></td>
      <td><input class="form-control lot" placeholder="opsional"></td>
      <td><input type="date" class="form-control expired"></td>
      <td><select class="form-select qc"><option value="released">Released</option><option value="pending">Pending</option><option value="quarantine">Quarantine</option><option value="rejected">Rejected</option></select></td>
      <td><button type="button" class="btn btn-sm btn-outline-danger rm">×</button></td>`;
    tb.appendChild(tr);
    tr.querySelector('.rm').addEventListener('click', ()=>tr.remove());
    reindex();
  }
  function reindex(){
    [...tb.rows].forEach((tr,idx)=>{
      tr.querySelector('.prod').name = `lines[${idx}][product_id]`;
      tr.querySelector('.qty').name = `lines[${idx}][qty]`;
      tr.querySelector('.cost').name = `lines[${idx}][cost]`;
      tr.querySelector('.lot').name = `lines[${idx}][lot]`;
      tr.querySelector('.expired').name = `lines[${idx}][expired]`;
      tr.querySelector('.qc').name = `lines[${idx}][qc]`;
    });
  }
  document.getElementById('add-line').addEventListener('click', addRow);
  addRow();
})();
</script>
@endsection

<div class="card mt-3"><div class="card-header"><h3 class="card-title">Baris Transaksi</h3></div>
<div class="card-body">
<div class="table-responsive"><table class="table" id="lines-table"><thead><tr><th>Produk</th><th style="width:120px">Qty</th><th style="width:160px">Harga</th><th></th></tr></thead>
<tbody></tbody></table></div>
<button type="button" class="btn btn-outline-primary" id="add-line">+ Baris</button>
</div></div>
<script>
(function(){
const prods = @json($products->map(fn($p)=>['id'=> is_array($p)?($p['id']??null):($p->id??null),'name'=> is_array($p)?($p['name']??'-'):(is_string($p->name??null)?$p->name:'-'),'price'=> (float)(is_array($p)?($p['sell_price']??$p['price']??0):($p->sell_price??$p->price??0))]));
const tb = document.querySelector('#lines-table tbody');
function addRow(){
const tr = document.createElement('tr');
let opts = prods.map(p=>`<option value="${p.id}" data-price="${p.price}">${p.name}</option>`).join('');
tr.innerHTML = `<td><select name="lines[][product_id]" class="form-select prod">${opts}</select></td>
<td><input name="lines[][qty]" type="number" step="any" min="0.0001" value="1" class="form-control qty"></td>
<td><input name="lines[][price]" type="number" step="any" min="0" value="0" class="form-control price"></td>
<td><button type="button" class="btn btn-sm btn-outline-danger rm">×</button></td>`;
tb.appendChild(tr);
const sel = tr.querySelector('.prod'), pr = tr.querySelector('.price');
const sync = ()=>{ pr.value = sel.selectedOptions[0].dataset.price; };
sel.addEventListener('change', sync); sync();
tr.querySelector('.rm').addEventListener('click', ()=>tr.remove());
}
// index lines[] dengan urutan: browser mengirim lines[0..n] otomatis? gunakan counter:
let i = 0;
document.getElementById('add-line').addEventListener('click', ()=>{
// tulis ulang name dengan index agar Laravel parse array rapi
addRow();
[...tb.rows].forEach((tr,idx)=>{
tr.querySelector('.prod').name = `lines[${idx}][product_id]`;
tr.querySelector('.qty').name = `lines[${idx}][qty]`;
tr.querySelector('.price').name = `lines[${idx}][price]`;
});
});
addRow();
})();
</script>

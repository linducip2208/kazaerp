<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><style>body{font-family:sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #999;padding:6px;text-align:left}.r{text-align:right}</style></head>
<body>
<h2>INVOICE {{ $row->number }}</h2>
<p>Tanggal: {{ tgl_id($row->date) }} • Jatuh tempo: {{ tgl_id($row->due_date) }}<br>Pelanggan: {{ $row->customer->name ?? '-' }}</p>
<table><thead><tr><th>Item</th><th class="r">Qty</th><th class="r">Harga</th><th class="r">Total</th></tr></thead>
<tbody>@foreach($row->lines as $l)<tr><td>{{ $l->snapshot_name }}</td><td class="r">{{ $l->qty }}</td><td class="r">{{ idr($l->price) }}</td><td class="r">{{ idr($l->line_total) }}</td></tr>@endforeach</tbody>
<tfoot><tr><th colspan="3" class="r">Grand Total</th><th class="r">{{ idr($row->grand_total) }}</th></tr></tfoot></table>
<p>Terima kasih atas kepercayaan Anda.</p>
</body></html>

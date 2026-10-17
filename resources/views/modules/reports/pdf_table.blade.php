<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $title }}</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
  h1 { font-size: 16px; margin: 0 0 2px; }
  .sub { color: #555; margin-bottom: 10px; font-size: 10px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #999; padding: 4px 6px; text-align: left; }
  th { background: #eee; }
  .num { text-align: right; }
  .total { margin-top: 8px; font-weight: bold; }
  .foot { margin-top: 12px; color: #555; font-size: 9px; }
</style>
</head>
<body>
<h1>{{ $title }}</h1>
<div class="sub">{{ $subtitle }} · Dicetak {{ tgl_id(now(), true) }} WIB</div>
<table>
<thead><tr>@foreach($headers as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
<tbody>
@forelse($rows as $row)<tr>@foreach($row as $c)<td>{{ $c }}</td>@endforeach</tr>
@empty<tr><td colspan="{{ count($headers) }}">Tidak ada data pada filter ini.</td></tr>@endforelse
</tbody>
</table>
@if($totalLine)<div class="total">{{ $totalLine }}</div>@endif
<div class="foot">KazaERP — dokumen sistem, dicetak dari laporan enterprise.</div>
</body>
</html>

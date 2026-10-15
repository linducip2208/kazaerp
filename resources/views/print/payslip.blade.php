<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><style>body{font-family:sans-serif;font-size:12px}</style></head>
<body>
<h2>SLIP GAJI {{ $slip->number }}</h2>
<p>Nama: {{ $slip->name }} ({{ $slip->nip }})<br>Gaji bersih: <strong>{{ idr($slip->net) }}</strong></p>
<p><em>Dokumen rahasia — hanya untuk karyawan bersangkutan dan pihak berwenang.</em></p>
</body></html>

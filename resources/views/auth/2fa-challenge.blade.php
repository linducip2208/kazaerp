<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verifikasi 2FA — KazaERP</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="d-flex flex-column">
<div class="page page-center"><div class="container container-tight py-4">
<div class="card card-md"><div class="card-body">
<h2 class="h2 text-center mb-4">Kode authenticator</h2>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('2fa.verify') }}">@csrf
<div class="mb-3"><label class="form-label">Kode 6 digit</label><input name="code" class="form-control text-center" inputmode="numeric" autocomplete="one-time-code" required autofocus></div>
<button class="btn btn-primary w-100">Verifikasi</button>
</form></div></div></div></div></body></html>

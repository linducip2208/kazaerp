<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset Kata Sandi — KazaERP</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="d-flex flex-column">
<div class="page page-center"><div class="container container-tight py-4">
<div class="card card-md"><div class="card-body">
<h2 class="h2 text-center mb-4">Reset kata sandi</h2>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ $email }}" required></div>
<div class="mb-3"><label class="form-label">Kata sandi baru (min 8)</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Konfirmasi</label><input type="password" name="password_confirmation" class="form-control" required></div>
<button class="btn btn-primary w-100">Reset</button>
</form></div></div></div></div></body></html>

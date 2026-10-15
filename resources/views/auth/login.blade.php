<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk — KazaERP</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="d-flex flex-column">
<div class="page page-center"><div class="container container-tight py-4">
<div class="card card-md"><div class="card-body">
<h2 class="h2 text-center mb-4">KazaERP — Kazaka Group</h2>
<p class="text-center text-muted">Masuk ke ERP terpadu</p>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('login.attempt') }}">@csrf
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus></div>
<div class="mb-2"><label class="form-label">Kata sandi</label><input type="password" name="password" class="form-control" required></div>
<div class="mb-3"><label class="form-check"><input type="checkbox" name="remember" class="form-check-input"><span class="form-check-label">Ingat saya</span></label></div>
<button class="btn btn-primary w-100">Masuk</button>
</form>
<p class="text-center mt-3"><a href="{{ route('password.request') }}">Lupa kata sandi?</a></p>
<p class="text-center text-muted mt-3">Akun demo (non-produksi): admin@kazaka.local</p>
</div></div></div></div></body></html>

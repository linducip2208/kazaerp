<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') — KazaERP</title>
@vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="auth-split">
<div class="auth-brand d-none d-lg-flex">
<div class="auth-brand-inner">
<div class="d-flex align-items-center gap-2 mb-4">
<span class="auth-mark">K</span>
<span class="auth-brand-name">KazaERP</span>
<span class="badge auth-badge">Kazaka Group</span>
</div>
<h1 class="auth-tagline">ERP terpadu untuk operasi holding.</h1>
<p class="auth-desc">Penjualan, stok &amp; batch, keuangan, manufaktur, HR, dan armada — satu dasbor, satu kebenaran.</p>
<ul class="auth-points">
<li><strong>Kasir POS offline-first</strong><span>tetap jalan tanpa internet, sinkron otomatis</span></li>
<li><strong>Stok &amp; batch FEFO</strong><span>kedaluwarsa &amp; karantina terjaga</span></li>
<li><strong>Jurnal selalu balance</strong><span>audit integritas sekali klik</span></li>
</ul>
<div class="auth-foot">MZA Holdings • Asia/Jakarta (WIB)</div>
</div>
</div>
<div class="auth-form">
<div class="auth-form-inner">
<div class="d-lg-none text-center mb-4"><span class="auth-mark auth-mark-sm">K</span><div class="fw-bold mt-2">KazaERP</div></div>
<h2 class="h1 mb-1">@yield('heading')</h2>
<p class="text-muted mb-4">@yield('sub')</p>
@yield('form')
</div>
</div>
</body>
</html>

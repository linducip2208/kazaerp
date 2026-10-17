# Performance Audit — Dashboard & Shell (17 Okt 2026)

Cakupan: keluhan "dashboard kurang profesional" + "makin lambat setelah perubahan UI" (v0.5.5).
Metode: instrumentasi lokal (query listener + render penuh), lingkungan dev yang sama sebelum/sesudah.
Bukan klaim produksi: angka di bawah dari MySQL lokal `kazaerp` (8.4.9), bukan server produksi.

## 1. Baseline (sebelum perbaikan, harness CLI + render penuh Blade)
- Waktu controller + render: **±127 ms**, HTML ±31 KB
- Total query per load dashboard: **84**
  - `roles`/`permission_role`/`permissions`: **50** (N+1 otorisasi)
  - `invoices`: 10 (KPI + 6× loop tren bulanan + per-entitas)
  - Lainnya: approvals 3, vendor_invoices 2, stock_balances 2, sales_orders 2, dsb.
- `APP_DEBUG=1`, `APP_ENV=local` (dev; bukan temuan produksi).

## 2. Akar masalah (bukti kode)
1. **N+1 otorisasi (dominan, ~60% query).** `User::hasPermission()` menembak DB setiap
   panggilan (cek `roles` + join `permission_role`). Layout sidebar `tabler.blade.php:45–262`
   memanggilnya ±25× per halaman + ekstra di dashboard view — untuk SEMUA role,
   karena `hasRole('super_admin')` pun query dulu sebelum short-circuit.
2. **Agregasi invoice berulang.** `DashboardController` (pra-perbaikan): 3 KPI terpisah +
   loop 6× `sum()` untuk tren + 1 agregasi per-entitas = 10 scan tabel `invoices`.
   Di data kecil ini murah, tetapi berpola O(bulan) dan berlipat saat data tumbuh.
3. **Aset build besar (observasi, bukan blocker).** `public/build`: JS ±478 KB (gzip ±156 KB),
   CSS ±846 KB (gzip ±108 KB) — Tabler + Chart.js lokal (sesuai aturan tanpa CDN).
   `@vite` memakai `type=module` (deferred, tidak blocking). Belum ada code-splitting;
   dapat dipertimbangkan bila halaman publik ditambah.
4. Bukan penyebab: middleware/auth/session, cache miss, queue/scheduler, indeks hilang
   (`invoices(legal_entity_id,status)` compound + FK index ada; EXPLAIN tidak diminta karena
   data dev kecil — bottleneck terbukti di lapisan aplikasi, bukan planner).

## 3. Perbaikan (file berubah)
- `app/Models/User.php` — memoization request-scoped: `memoRoleNames()` + `memoPerms()`
  (per instance user; otomatis aman lintas user/request karena objek tidak dibagi).
  `flushAuthCache()` untuk pemakaian setelah perubahan role. Tanpa cache lintas
  entitas/role, tanpa pengurangan validasi/otorisasi/audit.
- `app/Http/Controllers/DashboardController.php` — 3 KPI invoice → 1 SELECT kondisional;
  tren 6 bulan 6 query → 1 `GROUP BY DATE_FORMAT`; kolom dibatasi (`get([...])`).
  Tidak ada cache angka keuangan (data selalu fresh); tidak ada perubahan aturan bisnis.
- Hasil: query dashboard **84 → 28** (`roles`/perm **50 → 1**, `invoices` **10 → 3**),
  waktu harness **±127 ms → ±86 ms** (skenario + data identik).

## 4. Hasil sesudah (bukti tes)
- Harness CLI render penuh: `result=OK time_ms=86 queries=28 roleperm_queries=1 html_bytes=31390`.
- `tests/Feature/DemoPerfTest.php` (baru, 4 tes, MySQL `kazaerp_testing`):
  `permission_memoized` (30× cek ≤2 query), `dashboard_200_dan_query_terbatas` (<45 query),
  `demo_seeder_idempotent`, `demo_login_dan_isolasi_entitas` → **4 passed / 23 assertions**.
- Regresi: `SalesFlowTest` + `SmokePagesTest` (2 passed), `AuthTest|IsolationTest|
  FinancePostingTest|PurchasingMatchTest` (7 passed). `npm run build` sukses (19,96s).
- HTTP nyata dev: `GET /login` via Apache 200 dalam 0,27s; hint demo tampil (dev),
  tanpa referensi CDN.

## 5. Keterbatasan
- Angka waktu adalah dev-lokal (mesin + data kecil); tidak diproyeksikan ke produksi.
- Suite penuh (±27 mnt) tidak dijalankan (larangan paralel); area berubah dicakup regresi di atas.
- Pint tidak dijalankan massal: file pre-existing pun tidak lolos gaya Pint (lihat FINALIZATION_AUDIT R4) — diff dijaga minimal.
- Index tambahan tidak dibuat: tidak terbukti dibutuhkan pada pola query saat ini.

## 6. Ronde polish 18 Okt 2026 (tanpa fitur baru)
Metode sama: listener query + MySQL `kazaerp_testing`, hitung terisolasi per halaman (satu request per proses tes agar tidak tercemar state sesi).

### 6.1 Aset: impor Bootstrap ganda dibuang
Bukti: `tabler.min.js` (135.175 byte) membundel Bootstrap 5.3.8 penuh (Toast/Modal/Dropdown/Collapse ada,
`window.bootstrap` diset) — versi identik dengan dep langsung `bootstrap@5.3.8`. `tabler.min.css` (635.382 byte)
sudah memuat gaya Bootstrap. Impor terpisah murni duplikat.
Perubahan: `resources/js/app.js` (hapus `import bootstrap.bundle`), `resources/css/app.css` (hapus
`@import bootstrap.min.css`), tanpa perubahan visual/logika (semua kelas Bootstrap tetap dari Tabler).
Hasil `npm run build` (3,27 dtk): JS 477.976 → 396.095 byte (−17%), CSS 845.761 → 649.309 byte (−23%);
gzip JS ±156 → 131 KB, CSS ±108 → 82 KB. Verifikasi: `GET /login` 200, tidak ada referensi CDN,
`build/assets/*` 200.

### 6.2 N+1 halaman berat: diukur, TIDAK terbukti ada
`tests/Feature/PolishPerfTest.php` (baru, 4 tes): `/crm/customers` 9 query, `/sales/orders` 8,
`/reports/sales` 10, `/finance/journals` 8 — semua 200. Breakdown `reports/sales`: 1× users, 1× roles,
1× paginate count, 1× agregat sum, 1× branches, 1× approvals, 2× notifications, 1× entities, 1× branches —
tanpa pengulangan per-baris (pola join/agregat/cursor sudah benar). Kesimpulan: tidak ada eager loading
atau indeks baru yang terbukti perlu; tidak ada migrasi skema pada ronde ini (aturan: aditif bila perlu).

### 6.3 CI bertarget
`.github/workflows/ci.yml` (baru): composer validate, `php -l` file PHP berubah, `route:list`,
`npm ci + build`, tes terarah `AuthTest|IsolationTest|SmokePagesTest|DemoPerfTest`.
Alasan targeted: suite penuh ±27 menit tidak layak per-push; suite penuh tetap manual pra-rilis.

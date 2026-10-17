# Redesign Dashboard Admin Enterprise — 2026-10-17

## Ringkasan
Navigasi horizontal padat diganti shell admin enterprise: sidebar kiri collapse + drawer mobile,
topbar ringkas, dashboard diprioritaskan (aksi → KPI → tren → aktivitas → keuangan/ops/HR).
Tanpa CDN, tanpa paket npm baru, tanpa perubahan skema/migrasi, tanpa data karangan.

## File diubah (7) + alasan
1. `resources/views/layouts/tabler.blade.php` — ditulis ulang: sidebar berkelompok (Utama,
   Organisasi & Akses, Operasional, Keuangan & SDM, Laporan & Tata Kelola) dengan active state
   `request()->routeIs()`, grup `<details>` yang otomatis terbuka di halaman aktif; topbar sticky
   (toggle, pencarian, pemilih entitas/cabang, approval, notifikasi, profil). Ikon SVG inline
   gaya Tabler (tanpa paket baru). Semua route/permission gate dipertahankan 1:1, termasuk Berkas
   yang sebelumnya tak punya menu (masuk grup CRM & Master, tetap `perm:crm.view` via route).
2. `resources/css/app.css` — ditambah: token layout, sidebar/topbar, rail collapse desktop
   (264px → 76px, tersimpan di localStorage), drawer + backdrop ≤991px, kartu KPI, banner
   prioritas, `.chart-wrap` 280px (240px di HP), stat tiles, tabel `.table-clean`, focus-visible,
   `prefers-reduced-motion`. Tidak menghapus gaya auth yang ada.
3. `resources/js/app.js` — ditambah: toggle collapse/drawer, tutup via backdrop/tombol/Escape,
   persist localStorage (aman bila storage tidak tersedia). Tidak mengubah perilaku confirm/toast.
4. `resources/views/dashboard.blade.php` — ditulis ulang dengan urutan: judul+periode+entitas,
   banner prioritas (approval pending → `gov.approvals.index`; stok menipis →
   `inventory.stock.index`), 4 kartu KPI (pendapatan bulan ini, piutang, utang, stok/approval),
   grafik tren + panel "Perlu perhatian" (5 approval terbaru + 5 item menipis + tombol aksi),
   tabel pendapatan per badan usaha + sales order terbaru, lalu kartu Keuangan/Operasional/HR.
   Setiap tautan digate permission yang sama dengan controller; tabel memakai `tgl_id()`/`idr()`.
5. `app/Http/Controllers/DashboardController.php` — ditambah: `$pendingList` (5 approval pending
   terbaru), `$lowItems` (5 item menipis: sku/nama/gudang/sisa), `$entityCode` untuk subtitle.
   Tidak mengubah agregat yang ada; hitungan approval global memang benar karena tabel
   `approvals` tidak punya `legal_entity_id` (skema tidak diubah).
6. `app/Http/Controllers/ReportController.php` — `sales()` kini menghormati `?q` (filter nomor
   invoice / nama pelanggan). Membuat kolom pencarian topbar (yang memang submit ke
   `reports.sales`) benar-benar berfungsi; sebelumnya parameter diabaikan.
7. `resources/views/modules/reports/sales.blade.php` — kolom pencarian `q` ditambah ke form
   filter agar konsisten dengan topbar.

## Audit data per widget
- Penjualan bulan ini / piutang / utang: agregat `invoices` ter-scope entitas via sesi, tidak
  hitung ganda; label eksplisit "nominal invoice/pendapatan, bukan laba" pada tabel per entitas.
- Tren 6 bulan: loop bulan kalender penuh; bila semua nol, grafik tetap dirender + pesan kosong
  (tidak mengklaim nol sebagai data hilang). Tooltip IDR penuh; sumbu-Y ringkas (Rp … jt).
- Stok menipis: `qty_on_hand - qty_reserved <= 5` ter-scope via `products.legal_entity_id`
  (kolom entitas memang hanya ada di produk, bukan di `stock_balances`).
- Approval pending: global (tanpa kolom entitas di skema) — dicatat di UI implisit via tautan
  ke halaman approval; tidak dipalsukan per entitas.
- Tanggal Asia/Jakarta via `tgl_id()`; IDR via `idr()`; query sensitif tetap di balik
  `perm:*` middleware + `hasPermission` di view.

## Pemeriksaan visual/responsive (statis + render-server)
- Desktop ≥992px: sidebar 264px, collapse ke rail ikon 76px (persist).
- Tablet/HP ≤991px: sidebar jadi drawer geser + backdrop, tombol tutup, Escape menutup.
- Grafik 280px (240px ≤575px), `maintainAspectRatio:false`, bar maks 44px, grid halus.
- Render terbukti: smoke test me-render `/` + 60 halaman (Blade terkompilasi tanpa error,
  semua 200). Verifikasi piksel-per-piksel di browser (screenshot/login manual) BELUM dilakukan
  — tidak ada kredensial dev yang dipakai agen; disarankan review visual manusia (lihat "Belum
  terverifikasi").

## Hasil build/test aktual
- `npm run build`: sukses (lihat TEST_RESULTS).
- `php artisan test --filter SmokePagesTest`: 1 passed / 3 assertions.
- `php artisan test --filter AuthTest`: 2 passed / 7 assertions.
- Full suite 27 menit TIDAK dijalankan (tidak diperlukan untuk cakupan perubahan).

## Belum bisa diverifikasi
- Tampilan visual nyata di browser (perlu login manual + screenshot desktop/tablet/mobile).
- Perilaku drawer/collapse dengan sentuhan nyata (kode mengikuti pola Bootstrap/offcanvas yang
  umum; smoke hanya membuktikan render server).

## Tidak dilakukan (sesuai batasan)
- Commit/push (working tree dibiarkan untuk review). Migrasi: tidak ada. DB produksi: tidak
  disentuh. Dependensi baru/CDN: tidak ada.

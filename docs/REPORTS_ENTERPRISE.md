# Laporan Enterprise + Impor Menyeluruh — KazaERP

Tanggal: 2026-10-17 · Branch: `kerja-erp-foundation` · Basis: v0.5.5 (tanpa migrasi skema, tanpa dependensi baru)

## 1. Inventarisasi awal (temuan)

| Area | Temuan |
|---|---|
| `reports/sales` | Ekspor CSV hanya mengekspor halaman aktif (paginasi), ringkasan mengabaikan filter `q`, tanggal mentah, tanpa filter status/cabang |
| `reports/inventory` | **Tombol "Export CSV" rusak** — controller tidak menangani `?export=csv`; tanggal mentah; tanpa ringkasan |
| `reports/purchase` | **Tombol "Export CSV" rusak**; view salah kolom (copy-paste: menampilkan `date` sebagai supplier, `grand_total` tanpa subtotal/pajak); **filter tanggal tidak diterapkan sama sekali** |
| `reports/aging` | Tanpa ekspor, tanpa filter kata kunci/gudang |
| Impor | Hanya `customers` + `products`, dan **tidak ada tautan** dari halaman mana pun (orphan) |
| Keuangan (`ledger/trial/pl/aging`) | Tanpa ekspor CSV |

## 2. Yang dikerjakan

**`app/Http/Controllers/ReportController.php`** (tulis ulang):
- Semua laporan: filter tanggal + pencarian + filter spesifik (status bayar/cabang/gudang/ambang hari), scope `legal_entity_id` dipertahankan, branch opsional.
- Agregat dihitung dari query filter yang sama (tidak lagi hitung ganda/abaikan filter).
- Ekspor **CSV** (hasil penuh via cursor, batas 5000 baris + catatan, escape anti formula-injection `= + - @`) dan **PDF** (paket yang sudah ada: `barryvdh/laravel-dompdf`; tidak install paket baru).
- Nilai kosong vs nol dibedakan (`due_date` kosong → `—`, mutasi terakhir kosong → `tidak tercatat`); tanggal via `tgl_id()`, uang via `idr()`; pagination + `withQueryString`; empty-state; tombol cetak (`window.print`).

**`app/Http/Controllers/ImportController.php`**: tambah impor **suppliers** (header `code,name` + opsional `phone,email,bank_account`; cek duplikat per entitas = idempotent; batas 2000 baris; audit log) + endpoint **template CSV** (`import/template/{name}`) untuk customers/products/suppliers.

**`app/Http/Controllers/Finance/FinanceReportController.php`**: ekspor CSV di `ledger`, `trialBalance`, `pl`, `aging` (AR/AP) — menghormati filter yang sama dengan tampilan.

**Rute** (`routes/modules/business.php`): `import/suppliers` (GET+POST), `import/template/{name}` — semua di balik `perm:crm.create`.

**View**: 4 laporan ditulis ulang ke standar enterprise (kartu ringkasan, filter lengkap, ekspor CSV+PDF, cetak); 3 view impor diperkaya (tombol template, contoh baris, pesan sukses); view impor suppliers baru; layout PDF generik `pdf_table.blade.php`; tombol "Impor CSV" ditambahkan di index pelanggan, supplier, produk (sebelumnya yatim); tombol "Ekspor CSV" + "Cetak" di 4 laporan keuangan; `due_date` aging keuangan memakai `tgl_id()`.

## 3. Hasil uji aktual

`php artisan test --filter ReportsEnterpriseTest` (MySQL `kazaerp_testing`, migrate:fresh per TestCase): **7 passed, 0 failed — 57 assertions** (±246 dtk).
- Halaman + filter + ringkasan penjualan; CSV penuh + PDF penjualan (PDF > 1 KB, content-type pdf).
- Pembelian: kolom benar + CSV + PDF.
- Inventori + stok menua: halaman + CSV.
- Keuangan: 4 halaman + 4 CSV.
- Impor supplier (valid + duplikat dilewati + header buruk ditolak) + 3 template + 3 halaman impor 200.
- Tautan "Impor CSV" terlihat di 3 halaman index.
- `php -l` bersih; `route:list --name=reports` 11 rute; tidak ada perubahan CSS/JS sehingga `npm run build` tidak diperlukan.

## 4. Catatan / belum diverifikasi

- PDF diekspor via dompdf yang sudah terinstal — terverifikasi via tes (200 + biner > 1 KB), belum diperiksa visual per halaman.
- Impor dibatasi pada master (pelanggan/produk/supplier) sesuai pola yang ada; impor dokumen transaksional (invoice/PO/stok) sengaja tidak ditambahkan — berisiko integritas ledger/jurnal; dicatat sebagai `PENDING_BUSINESS_DECISION` bila dibutuhkan.
- Suite penuh tidak dijalankan (durasi ±27 menit); hanya suite baru yang relevan + `php -l`/`route:list`.

"""Generator 12b: docs part 2."""
import os
BASE = r"D:\project laravel\kazaerp"
def w(rel, content):
    p = os.path.join(BASE, rel)
    os.makedirs(os.path.dirname(p), exist_ok=True)
    open(p, "w", encoding="utf-8", newline="\n").write(content)
    print("wrote", rel)

w("docs/UI_UX.md", """# UI/UX (Tabler lokal)

- Aset: `@tabler/core@1.6.1` + `bootstrap@5.3.8` via npm, bundel Vite → `public/build/assets/*` (CSS 835KB, JS 268KB). TIDAK ada CDN (layout hanya `@vite` + manifest lokal).
- Layout `layouts/tabler.blade.php`: topbar (brand, search, 🔔 approval badge, user menu), sidebar/navbar dropdown PER PERMISSION (tidak semua menu tampil), context switcher entity/cabang (terotorisasi), breadcrumb per halaman, toast/alert, empty state, pagination, modal konfirmasi, responsive (collapse ≤768px), print CSS (`.no-print`).
- Komponen Blade: `crud-index` (search/filter/paginate/badges), `crud-form` (text/number/date/email/password/textarea/select/select_model), `crud-show`, `doc-lines-form` (tambah/hapus baris + harga otomatis).
- Bahasa Indonesia, format `idr()`/`tgl_id()`, timezone tampil Asia/Jakarta.
- Halaman: login, profil, dashboard (4 KPI + pendapatan/entitas + SO terbaru), 40+ listing/detail/create/edit, approval, laporan, import, 403/404/500.
- Aksesibilitas: label form, kontras Tabler default; audit lanjutan pending.
""")

w("docs/TEST_PLAN.md", """# Test Plan

- Unit: DocNumberService monotonik (`tests/Unit/DocNumberTest.php`).
- Feature (MySQL `kazaerp_testing`, `migrate:fresh` per method): AuthTest (login OK/gagal/nonaktif/guest), IsolationTest (lintas entitas 404, payroll 403), SalesFlowTest (SO→approve→invoice→jurnal balance→partial/paid→overpayment & oversell ditolak), InventoryConcurrencyTest (reservasi paralel anti-oversell, partial receipt), PurchasingMatchTest (3-way exception→HOLD, duplikat vendor_ref), FinancePostingTest (balance, idempotency, reversal, tutup periode, SoD), BusinessRulesTest (rental bentrok, thrift unik, boarding capacity, lead convert, report filter).
- Concurrency: locking DB nyata (bukan SQLite) — reservasi 3×2 dari stok 5 ⇒ 2 sukses 1 gagal.
- Acceptance: checklist + smoke HTTP 22 halaman (login, CRUD, POS, laporan) — bukti di TEST_RESULTS.md.
- Regresi: full `php artisan test` tiap perubahan; static: `php -l` per file + `composer validate`.
""")

w("docs/INSTALLATION.md", """# Installation (local)

1. PHP 8.2+ + ext pdo_mysql mbstring openssl tokenizer xml ctype json bcmath fileinfo; Composer; Node 18+; MySQL 8.
2. `composer install`
3. `cp .env.example .env`, isi `DB_DATABASE=kazaerp`, `APP_URL`, `APP_TIMEZONE=Asia/Jakarta`.
4. `php artisan key:generate`
5. `mysql -u root -e "CREATE DATABASE kazaerp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE kazaerp_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`
6. `php artisan migrate --force && php artisan db:seed --force`
7. `npm install && npm run build`
8. `php artisan serve --port=8000` → http://localhost:8000 (dev: admin@kazaka.local / Kazaka#2026)
9. Test: `php artisan test` (memakai `kazaerp_testing`).

Troubleshooting: DB access denied → cek DB_USERNAME/PASSWORD; `vite manifest missing` → `npm run build`; 500 → `storage/logs/laravel.log`; permission storage → `chmod -R 775 storage bootstrap/cache` (Linux).
""")

w("docs/DEPLOYMENT.md", """# Deployment (Laravel + MySQL)

- Document root: `public/`. HTTPS wajib. `APP_ENV=production APP_DEBUG=false`.
- `.env` produksi: APP_KEY baru, DB kredensial kuat, MAIL, `KAZA_COGS_METHOD=none`.
- `composer install --no-dev --optimize-autoloader; php artisan config:cache; route:cache; view:cache; npm run build`.
- Migrasi: `php artisan migrate --force` (jangan `fresh` di produksi!). Seeder demo JANGAN dijalankan (`DemoSeeder` menolak di production).
- Akun awal: buat via `db:seed --class=AdminSeeder` (pending: sediakan seeder admin interaktif) lalu hapus kredensial default; paksa ganti password.
- Permission file: `storage bootstrap/cache` writable; cron scheduler tiap menit; queue worker bila dipakai (`queue:work --daemon` + supervisor).
- Backup: dump MySQL harian + `storage/app/private`; uji restore berkala (lihat OPERATIONS_RUNBOOK).
- Rollback: `php artisan migrate:rollback --step=N` + restore backup; catat di CHANGELOG.
""")

w("docs/API.md", """# API

Tidak ada REST API publik pada tahap ini (aplikasi server-rendered Blade adalah arsitektur utama — disengaja).
Endpoint mesin: `GET /up` (health), `GET /health` (auth). Jika API diperlukan (aplikasi mobile/POS eksternal),
rencana: Sanctum + versioning `/api/v1`, validasi Form Request, format error `{message, errors}`, idempotency key
untuk posting. Status: PENDING (lihat ROADMAP).
""")

w("docs/USER_MANUAL.md", """# User Manual (ringkas)

1. Login dengan email + kata sandi. 2. Pilih konteks Badan Usaha/Cabang di topbar (hanya yang berhak).
3. Dashboard menampilkan KPI sesuai peran. 4. Master: CRM → Pelanggan/Supplier; Master → Produk.
5. Jual: Sales → SO (isi baris, simpan) → Setujui → Invoice (otomatis posting + potong stok) → Bayar (partial boleh; status ikut total) → cetak PDF.
6. Kasir: POS → buka shift → checkout → tutup shift (catat selisih).
7. Beli: PR → PO → terima barang (bisa partial) → invoice vendor (cek status match; HOLD bila beda) .
8. Keuangan: Jurnal (wajib balance) → Buku Besar/Neraca/Laba Rugi → tutup periode tiap bulan.
9. HR: Karyawan → Absensi → Payroll (hitung → setujui → slip PDF).
10. Approval: menu ✅ — setujui/tolak; pemohon tidak bisa setujui sendiri.
11. Laporan: filter tanggal → Export CSV. Import: Reports → Import → upload CSV → perbaiki baris error.
Masalah umum: "Stok tidak mencukupi" → terima barang dulu; "Periode ditutup" → minta Finance buka kembali; 403 → minta peran/scope.
""")

w("docs/IMPLEMENTATION_STATUS.md", """# Implementation Status (per 15 Okt 2026)

## Selesai (DONE, bukti run nyata)
- Foundation: Laravel 11.57 + PHP 8.3.30 + MySQL 8.4.9; 173 tabel; migrate:fresh hijau; seed hijau.
- 57 controller, 150 Blade, ~150 routes (`route:list` 325 baris), Tabler lokal build 835KB/268KB.
- Smoke HTTP: 22/22 halaman 200 (guest redirect, login, CRUD, POS, laporan).
- E2E nyata: GRN→SO→approve→invoice→jurnal balance→partial→paid (DB diverifikasi).
- Test: suite penuh MySQL (lihat TEST_RESULTS.md).

## Parsial (berfungsi inti, perlu pendalaman)
Intercompany (register saja), harga tier/karantina, output/variance MFG, file private studio, komisi, grafik tren, delegasi approval UI, mapping jurnal payroll, scheduler, queue worker, 2FA/reset mandiri.

## Tertunda (PENDING)
API publik, eliminasi otomatis, payment gateway/marketplace/WA/GPS, transaksi offline, HPP FIFO/average, kebijakan pajak/payroll resmi.

## Blocker
Tidak ada blocker teknis. Blocker bisnis: lihat DECISIONS_AND_OPEN_QUESTIONS.md.
""")

w("docs/DECISIONS_AND_OPEN_QUESTIONS.md", """# Decisions & Open Questions

## Keputusan arsitektur
- Modular monolith Laravel (bukan microservices); namespace domain `App\\Models/<Domain>` + `app/Services` (didokumentasikan di ARCHITECTURE.md).
- RBAC native (tanpa package) agar tanpa dependensi tambahan; 16 role/60+ permission.
- DB UTC → tampil Asia/Jakarta; IDR; decimal 18,2/18,4.
- Error bisnis → pesan validasi ramah (bukan 500).

## PENDING_BUSINESS_DECISION (butuh owner/Finance)
1. Blueprint PDF resmi belum ditemukan — mapping memakai Master Build Command; mohon sediakan file.
2. Daftar legal entity & brand final (termasuk status ZAYAN Style, hubungan ZRYN ID/ZRYN Denim).
3. Metode HPP (FIFO vs average vs none) — saat ini `none`.
4. Kebijakan intercompany + eliminasi konsolidasi.
5. Tarif pajak, PPN, PPh, e-faktur — verifikasi konsultan.
6. Aturan payroll: UMK, BPJS, lembur, pajak — verifikasi HR/Finance.
7. Provider payment gateway, marketplace, WhatsApp/SMS, GPS — kredensial + pilihan vendor.
8. Kebijakan deposit sewa & pengakuan pendapatan; retensi data; hardware (barcode/printer).
""")

w("docs/CHANGELOG.md", """# Changelog

## v0.1.0 — 2026-10-15
- Bootstrap Laravel 11 + MySQL kazaerp/kazaerp_testing + Tabler lokal + dompdf.
- 10 file migrasi (173 tabel), 100+ model, 5 service inti, 57 controller, 150 view, ~150 routes.
- Seeder role/permission/org/demo; auth + konteks + audit.
- Alur SO→invoice→bayar, POS+shift, GRN partial, PO+3way match, jurnal balance/idempotent/reversal, payroll, pet, mfg, thrift, studio, rental, marketing, approval+SoD, laporan+CSV+import, PDF.
- 8 file test (17 test); smoke 22/22; docs 26 file + AGENTS.md.
""")

w("docs/ACCEPTANCE_CHECKLIST.md", """# Acceptance Checklist

## Fungsional (tandai saat UAT)
- [x] Login/logout, akun nonaktif ditolak, throttle
- [x] Konteks entity/cabang + isolasi (test)
- [x] CRUD master + import validasi
- [x] SO→approve→invoice→bayar→PDF (E2E)
- [x] POS shift + selisih
- [x] Ledger + reservasi + partial receipt (test)
- [x] PR→PO→GRN→VIN + HOLD + duplikat guard (test)
- [x] Jurnal balance/idempotent/reversal + closing guard (test)
- [x] TB/PL/ledger/aging tampil
- [x] Payroll hitung→setuju→slip
- [x] Boarding/rental/thrift guards (test)
- [x] Approval + SoD (test)
- [ ] UAT tanda tangan owner & Finance (PENDING)

## Keamanan/Data/Deploy/Go-live
- [x] Permission per menu, 403/404/500, tanpa CDN, secret tidak di-log
- [x] Migrate fresh + seed + build + route:list + smoke
- [ ] HTTPS, backup/restore teruji, kredensial produksi, reset password mandiri (lihat DEPLOYMENT)
- [ ] Keputusan PENDING_BUSINESS_DECISION disetujui sebelum go-live
""")

w("docs/OPERATIONS_RUNBOOK.md", """# Operations Runbook

- Backup: `mysqldump -u root kazaerp | gzip > backup-$(date +%F).sql.gz` + arsip `storage/app/private`; simpan offsite; retensi 30 harian + 12 bulanan (minta persetujuan).
- Restore: buat DB kosong → `gunzip < backup.sql.gz | mysql kazaerp` → `php artisan migrate --force` (verifikasi) → cek `IMPLEMENTATION_STATUS`.
- Log: `storage/logs/laravel.log`; antrean gagal: `php artisan queue:failed` / `queue:retry`.
- DB maintenance: `ANALYZE/OPTIMIZE` berkala; pantau `stock_balances` vs `stock_ledgers` (rekonsiliasi: sum ledger vs saldo).
- Insiden: bekukan posting (maintenance mode), snapshot DB, periksa audit_logs + login_activities, reversal (jangan hapus).
- Closing bulanan: rekonsiliasi bank → review unposted → tutup periode → backup.
- Recovery: RTO target 4 jam, RPO 24 jam (sesuaikan + uji).
""")

w("docs/ROADMAP.md", """# Roadmap

- v0.2: delegasi approval UI, grafik tren, notifikasi DB, payment AP, retur pembelian stok, audit viewer read-only penuh.
- v0.3: mapping jurnal payroll, eliminasi intercompany (setelah kebijakan), HPP FIFO/average (setelah keputusan), e-faktur adapter.
- v0.4: Sanctum API v1 (POS/mobile), sinkronisasi marketplace adapter nyata, GPS transport.
- v1.0: UAT + hardening produksi (2FA, header, backup teruji) + go-live bertahap per entitas.
""")
print("gen12b done")

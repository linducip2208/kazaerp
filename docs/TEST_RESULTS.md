# Test Results (dari run nyata — bukan klaim)

## Suite penuh v0.3.0 — 2026-10-15 (MySQL `kazaerp_testing`)
- Perintah: `php artisan test`
- Hasil: **26 passed, 0 failed, 0 skipped — 119 assertions**
- 20 test v0.1–v0.2 tetap hijau (regresi nihil) + 6 test Phase3Test baru:
  - 2FA penuh (setup QR → confirm → challenge → dashboard non-admin 200; sekaligus menemukan & memperbaiki bug `$perEntity` array untuk non-admin)
  - file privat (403 lintas entitas), vet butuh pet.treat, alokasi equal & price,
  - voucher (salah ditolak, diskon + used + poin), bankrec matched/draft + budget/aging/intercompany OK
- DB pengujian: MySQL (locking & constraints nyata). Log: `storage/logs/finaltest3.log`.

## Riwayat v0.2.0 (arsip)
- Hasil v0.2.0: **20 passed — 82 assertions** (`storage/logs/finaltest2.log`).
- 16 test v0.1.0 tetap hijau (regresi nihil) + 4 test Phase2Test baru:
  - bayar utang partial/paid + overpay ditolak + jurnal balance
  - payroll approve → jurnal gaji Rp4.500.000 balance (6100→2300)
  - delegasi memungkinkan decide tanpa permission langsung
  - API login/scope-403/revoke-401/nonaktif-401
- DB pengujian: MySQL (locking & constraints nyata). Log: `storage/logs/finaltest2.log`.
- Rincian file:
  - `tests/Unit/DocNumberTest.php` — 1 passed (sequence monotonik + format)
  - `tests/Feature/AuthTest.php` — 2 passed (login OK/gagal/nonaktif, guest redirect)
  - `tests/Feature/IsolationTest.php` — 2 passed (lintas entitas 404 + konten tersaring; payroll 403 untuk cashier)
  - `tests/Feature/SalesFlowTest.php` — 1 passed (SO→approve→invoice→jurnal balance→partial→paid→overpayment & oversell ditolak)
  - `tests/Feature/InventoryConcurrencyTest.php` — 2 passed (reservasi paralel 3×2 dari stok 5 ⇒ 2 sukses/1 gagal; partial receipt partial→received)
  - `tests/Feature/PurchasingMatchTest.php` — 1 passed (3-way exception→HOLD; duplikat vendor_ref ditolak)
  - `tests/Feature/FinancePostingTest.php` — 2 passed (balance/idempotent/reversal/tutup-periode; SoD self-approve ditolak, orang lain bisa)
  - `tests/Feature/BusinessRulesTest.php` — 5 passed (rental bentrok, thrift unik, boarding capacity, lead convert, report filter)
- DB pengujian: MySQL (phpunit.xml → `kazaerp_testing`), menguji locking & constraints nyata. Tidak memakai SQLite.
- Log: `storage/logs/finaltest.log`.

## Riwayat perbaikan (run pertama: 13 passed, 4 failed)
1. `SalesFlowTest` overpayment/oversell → 500 karena RuntimeException tak tertangkap. Perbaikan: semua aksi transaksional dibungkus → `back()->withErrors(['msg'])` (sekaligus UX lebih baik).
2. `FinancePostingTest` SoD → 500/403. Perbaikan: withErrors di ApprovalController + test memberi permission `governance.approve` dulu agar mencapai guard SoD; verifikasi dua sisi (sendiri ditolak, orang lain bisa).
3. `InventoryConcurrencyTest` approve PO → SoD meledak (pembuat = penyetuju). Perbaikan: test memakai dua user berbeda (praktik SoD yang benar).
4. `BusinessRulesTest` lead convert → 403. Perbaikan: role `module_operator` diberi permission operasional di seeder.

## Pemeriksaan lain (nyata)
- `composer validate` → valid. `php artisan migrate:fresh --force` → 13 migrasi DONE (173 tabel). `php artisan db:seed` → 3 seeder DONE.
- `php artisan route:list` → ±160 routes, tanpa error. `php artisan view:cache` → sukses. `npm run build` → CSS 835KB + JS 268KB lokal.
- `php -l` seluruh `app/` (100+ file) → 0 error sintaks.
- Smoke HTTP (login sebagai admin dev): 30 URL → semua 200 (guest → redirect login; forgot-password guest 200 setelah perbaikan layout).
- E2E manual terverifikasi di DB: GRN 10 → SO 2 → approve (reservasi) → invoice (stok 8, jurnal balance 166.500) → bayar 66.500 (partial) → 100.000 (paid); overpayment 999.999.999 → ditolak ramah.

## Error tersisa
Tidak ada error yang diketahui pada suite dan smoke di atas. Keterbatasan (bukan error): lihat IMPLEMENTATION_STATUS (parsial/pending).

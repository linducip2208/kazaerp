# Test Plan

- Unit: DocNumberService monotonik (`tests/Unit/DocNumberTest.php`).
- Feature (MySQL `kazaerp_testing`, `migrate:fresh` per method): AuthTest (login OK/gagal/nonaktif/guest), IsolationTest (lintas entitas 404, payroll 403), SalesFlowTest (SO→approve→invoice→jurnal balance→partial/paid→overpayment & oversell ditolak), InventoryConcurrencyTest (reservasi paralel anti-oversell, partial receipt), PurchasingMatchTest (3-way exception→HOLD, duplikat vendor_ref), FinancePostingTest (balance, idempotency, reversal, tutup periode, SoD), BusinessRulesTest (rental bentrok, thrift unik, boarding capacity, lead convert, report filter).
- Concurrency: locking DB nyata (bukan SQLite) — reservasi 3×2 dari stok 5 ⇒ 2 sukses 1 gagal.
- Acceptance: checklist + smoke HTTP 22 halaman (login, CRUD, POS, laporan) — bukti di TEST_RESULTS.md.
- Regresi: full `php artisan test` tiap perubahan; static: `php -l` per file + `composer validate`.

# Test Plan

- Unit: DocNumberService monotonik (`tests/Unit/DocNumberTest.php`).
- Feature (MySQL `kazaerp_testing`, `migrate:fresh` per method): AuthTest (login OK/gagal/nonaktif/guest), IsolationTest (lintas entitas 404, payroll 403), SalesFlowTest (SO→approve→invoice→jurnal balance→partial/paid→overpayment & oversell ditolak), InventoryConcurrencyTest (reservasi paralel anti-oversell, partial receipt), PurchasingMatchTest (3-way exception→HOLD, duplikat vendor_ref), FinancePostingTest (balance, idempotency, reversal, tutup periode, SoD), BusinessRulesTest (rental bentrok, thrift unik, boarding capacity, lead convert, report filter).
- Concurrency: locking DB nyata (bukan SQLite) — reservasi 3×2 dari stok 5 ⇒ 2 sukses 1 gagal.
- Phase2 (`tests/Feature/Phase2Test.php`): bayar utang partial/paid + overpay ditolak + jurnal balance; payroll approve → jurnal gaji 4.500.000 balance;
  delegasi memungkinkan decide tanpa permission; API login/scope-403/revoke/401-nonaktif.
- Acceptance: checklist + smoke HTTP 22 halaman (login, CRUD, POS, laporan) — bukti di TEST_RESULTS.md.
- Regresi: full `php artisan test` tiap perubahan; static: `php -l` per file + `composer validate`.

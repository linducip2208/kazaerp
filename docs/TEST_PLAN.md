# Test Plan

- Unit: DocNumberService monotonik (`tests/Unit/DocNumberTest.php`).
- Feature (MySQL `kazaerp_testing`, `migrate:fresh` per method): AuthTest (login OK/gagal/nonaktif/guest), IsolationTest (lintas entitas 404, payroll 403), SalesFlowTest (SO→approve→invoice→jurnal balance→partial/paid→overpayment & oversell ditolak), InventoryConcurrencyTest (reservasi paralel anti-oversell, partial receipt), PurchasingMatchTest (3-way exception→HOLD, duplikat vendor_ref), FinancePostingTest (balance, idempotency, reversal, tutup periode, SoD), BusinessRulesTest (rental bentrok, thrift unik, boarding capacity, lead convert, report filter).
- Concurrency: locking DB nyata (bukan SQLite) — reservasi 3×2 dari stok 5 ⇒ 2 sukses 1 gagal.
- Phase2 (`tests/Feature/Phase2Test.php`): bayar utang partial/paid + overpay ditolak + jurnal balance; payroll approve → jurnal gaji 4.500.000 balance;
  delegasi memungkinkan decide tanpa permission; API login/scope-403/revoke/401-nonaktif.
- Phase3 (`tests/Feature/Phase3Test.php`): 2FA penuh (setup QR, confirm, challenge, dashboard non-admin 200);
  file privat (upload OK se-entitas, 403 lintas entitas); vet butuh pet.treat; alokasi equal dan price;
  voucher (salah ditolak, diskon + used + poin loyalty); bankrec matched/draft + halaman budget/aging/intercompany.
- Phase4 (`tests/Feature/Phase4Test.php`): harga tier (invoice default tier + override + halaman + API price/tier pelanggan);
  GRN lot → batch otomatis, karantina/expired memblokir stok keluar, audit `quality_checks`; WO snapshot biaya standar → issue (aktual) → produce → variance + issue ulang ditolak;
  komisi akrual saat invoice lunas (rate config) + approve Finance; API POS shift/open/close/checkout (harga tier + potong stok + selisih) + GPS positions push/read + 403 lintas entitas;
  queue overdue alerts (dispatch via Queue::fake + eksekusi job + idempoten 24 jam + invoice lunas dilewati) + smoke halaman baru.
- Phase5 (`tests/Feature/Phase5Test.php`): read-only enforcement (auditor baca lintas modul OK & tulis 403; viewer tetap 403 walau diberi permission tulis; auditor+branch_manager boleh menulis; self-service profil diizinkan);
  reset password mandiri penuh (email tak dikenal ditolak; token diambil dari notifikasi; token salah ditolak; reset sukses → hash baru, password lama mati);
  queue worker nyata (job di-dispatch ke antrean database → `queue:work --once` memproses → notifikasi terbuat & antrean bersih);
  backup command (file .gz terbentuk, > 1KB, magic gzip; skip bila `mysqldump` tidak tersedia).
- Phase6 (`tests/Feature/Phase6Test.php`): idempotensi checkout POS via `client_ref` (201 baru → 200 replay, id/no sama, stok tidak dobel, retry tetap aman setelah shift ditutup; ref berbeda → penjualan baru);
  `kaza:audit-integrity` (kondisi bersih → exit 0; kerusakan saldo stok/jurnal/invoice terdeteksi → exit 1 + nomor dokumen);
  rate limit API (60 request/menit OK, ke-61 → 429).
- Phase7 (`tests/Feature/Phase7Test.php`): `kaza:admin-create` (buat + idempoten per email + validasi password/email);
  expiry alerts (dispatch queue 2 kandidat ≤30 hari, sync → notifikasi hanya batch berstok, idempoten 24 jam, threshold `--days=60` menangkap batch jauh, stok habis → tidak ada alert).
- Smoke otomatis (`tests/Feature/SmokePagesTest.php`): 70 halaman menu utama semua 200 untuk super_admin (menggantikan smoke manual).
- Mobile (`mobile/pos_kaza`, `flutter test`): 21 test — API client (login sukses/401/checkout replay/parse tier/jaringan mati/non-JSON), model & keranjang, repository antrean (persist lintas instance, sukses 201, replay 200, jaringan mati → antrean utuh, 422 → rejected → retry sukses, submitNew simpan-dulu). Plus `flutter analyze` (0 issue) + `flutter build web --release` (bukti kompilasi).
- Smoke kontrak live (`dart run tool/api_smoke.dart ... --checkout`, server dev 8099, 16 Okt 2026): login → katalog → buka shift (kas 100.000) → checkout `MZA-HOLD/PST/POS/2026/10/00001` total 83.250 → kirim ulang `client_ref` sama → **200 `replayed: true`, nomor & id sama** → tutup shift (sistem 83.250, selisih 0). Bukti end-to-end idempotensi dari kode klien asli (log `storage/logs/flutter_live_smoke.log`).
- Acceptance: checklist + smoke HTTP 22 halaman (login, CRUD, POS, laporan) — bukti di TEST_RESULTS.md.
- Regresi: full `php artisan test` tiap perubahan; static: `php -l` per file + `composer validate`.

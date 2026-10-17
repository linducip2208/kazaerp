# Security

## Threat model
Aktor: pengguna internal lintas cabang, mantan karyawan (akun aktif), penyerang kredensial. Aset: data keuangan, gaji, rekam medis, file pelanggan. Batasan: single-server Laravel/MySQL.

## Kontrol terimplementasi
- Auth: hash bcrypt, akun nonaktif ditolak, throttle login 10/menit, session regenerate, CSRF, logout invalidasi.
- 2FA TOTP opsional per pengguna (QR SVG lokal, challenge setelah password, disable wajib password, diaudit).
- Sesi dicabut seketika bila akun dinonaktifkan (middleware CheckActiveUser).
- Header keamanan: nosniff, SAMEORIGIN, referrer; HSTS + CSP ketat di production.
- Authorization: middleware `perm` + scope entity di semua query sensitif + `inScope()`; super_admin bypass eksplisit.
- Read-only enforcement: middleware `ReadOnlyRoles` — akun yang hanya berperan auditor/viewer tidak bisa menulis (non-GET ditolak 403) meski permission tulis salah dikonfigurasi; whitelist self-service (logout, konteks, profil, 2FA, tandai notifikasi). Bukti: `tests/Feature/Phase5Test.php`.
- API v1: semua endpoint butuh token ability `api.access` + guard entitas per resource (hanya peran lintas-entitas yang boleh akses lintas entitas); POS/GPS juga cek `pos.operate`/`pos.shift`/`pet.transport`; rate limit 60 request/menit per pengguna (429).
- Isolasi dibuktikan IsolationTest (404 lintas entitas, payroll 403 untuk cashier).
- Audit: `audit_logs` (aktor, waktu, entitas, aksi, ref, perubahan — tanpa secret) + `login_activities` + `doc_status_histories`.
- File: upload validasi tipe/ukuran (maks 10MB, daftar mime eksplisit) ke `storage/app/private`;
  download SELALU lewat controller terotorisasi per tipe pemilik (pelanggan dalam entitas, medis hanya klinis,
  karyawan hanya HR/pemilik) + tercatat di audit. Tidak ada URL publik ke file sensitif.
- Error: halaman 403/404/500 tanpa stack trace; log server-side saja.
- Secret: `.env` tidak di-commit (hanya `.env.example`); tidak ada secret di log/audit.

## Belum / hardening produksi
HTTPS, header keamanan (CSP/HSTS), 2FA, password policy + reset mandiri, session invalidation massal saat revoke (parsial: cek `is_active` tiap request — pending middleware), rate-limit global, backup terenkripsi, disk private + antivirus upload. Lihat DEPLOYMENT + ROADMAP.

## Triase audit dependensi 18 Okt 2026 (tanpa upgrade mayor)
- `composer audit`: 4 advisory pada `laravel/framework` v11.57.0 (terbaru di lini v11; fix hanya di v12.60+/12.61+/12.69+).
  Upgrade mayor v12 = BACKLOG (risiko breaking + PHP/dep; catat di DECISIONS_AND_OPEN_QUESTIONS).
  - GHSA-5vg9 (CRLF injection via aturan `email`, tinggi): **dimitigasi** — CR/LF dibuang sebelum validasi di
    login, lupa/reset kata sandi, dan create/update pengguna (`AuthController`, `PasswordResetController`,
    `UserController`); nilai tervalidasi aman dipakai sebagai header email. Bukti: `SecurityAuditTest::test_email_crlf_dinetralkan`.
  - GHSA-crmm (signed-URL path confusion, sedang): **tidak tereksploitasi** — tidak ada signed route di aplikasi
    (`signedRoute`/`hasValidSignature` nihil); tetap backlog hingga upgrade v12.
  - GHSA-jh5r (XSS halaman debug, rendah): **dimitigasi konfigurasi** — `APP_DEBUG=false` di `.env.example`;
    verifikasi `kaza:health` gagal bila `APP_DEBUG=true` di production.
- `npm audit --omit=dev`: 0 kerentanan.
- Penguatan ronde ini: header `X-Permitted-Cross-Domain-Policies: none` + CSP production diperketat
  (`object-src 'none'`, `base-uri 'self'`, `form-action 'self'`, `frame-ancestors 'self'` — tanpa mengubah
  `script-src`, jadi tidak merusak inline script yang sudah ada).
- Jejak audit dilengkapi (12 aksi, pola `AuditService::log` yang sama): approve/reject keputusan governance
  (`gov.approvals.decide`), bayar utang (`ap_payments.store`), konfirmasi rekonsiliasi bank, approve
  PO/quotation/credit-note/delivery-order/retur/SO/payroll, cancel SO, reverse jurnal. Bukti:
  `SecurityAuditTest::test_approve_mencatat_audit_log`. Ekspor CSV/PDF laporan sengaja belum diaudit
  (volume tinggi; PENDING_BUSINESS_DECISION bila dibutuhkan).
- Operasional: drill restore 18 Okt 2026 — dump dev `kazaerp` (4,7 dtk, 624 KB) → restore ke `kazaerp_scratch` →
  verifikasi identik (180 tabel; users 7, invoices 2, journals 6, audit_logs 2, products 5) → scratch di-DROP.
  Command baru `php artisan kaza:health` (DB, jobs/failed_jobs, storage writable, APP_DEBUG di production;
  exit 1 bila temuan — cocok untuk cron). Bukti: `HealthCheckTest`.

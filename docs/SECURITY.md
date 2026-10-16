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

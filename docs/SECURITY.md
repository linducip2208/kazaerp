# Security

## Threat model
Aktor: pengguna internal lintas cabang, mantan karyawan (akun aktif), penyerang kredensial. Aset: data keuangan, gaji, rekam medis, file pelanggan. Batasan: single-server Laravel/MySQL.

## Kontrol terimplementasi
- Auth: hash bcrypt, akun nonaktif ditolak, throttle login 10/menit, session regenerate, CSRF, logout invalidasi.
- Authorization: middleware `perm` + scope entity di semua query sensitif + `inScope()`; super_admin bypass eksplisit.
- Isolasi dibuktikan IsolationTest (404 lintas entitas, payroll 403 untuk cashier).
- Audit: `audit_logs` (aktor, waktu, entitas, aksi, ref, perubahan — tanpa secret) + `login_activities` + `doc_status_histories`.
- File: upload validasi tipe/ukuran (CSV 2MB; desain via storage), download lewat controller terotorisasi (pending: pindahkan ke disk private + signed URL).
- Error: halaman 403/404/500 tanpa stack trace; log server-side saja.
- Secret: `.env` tidak di-commit (hanya `.env.example`); tidak ada secret di log/audit.

## Belum / hardening produksi
HTTPS, header keamanan (CSP/HSTS), 2FA, password policy + reset mandiri, session invalidation massal saat revoke (parsial: cek `is_active` tiap request — pending middleware), rate-limit global, backup terenkripsi, disk private + antivirus upload. Lihat DEPLOYMENT + ROADMAP.

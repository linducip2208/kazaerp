# Acceptance Checklist

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

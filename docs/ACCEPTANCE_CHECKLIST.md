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
- [x] Harga tier otomatis (SO/invoice/quote/POS/API) (test)
- [x] QC/karantina batch + guard stok keluar (test)
- [x] MFG issue→produce + variance biaya (test)
- [x] Komisi akrual saat lunas + approve Finance (test)
- [x] API POS (shift/checkout) + GPS positions (test)
- [x] Notifikasi jatuh tempo via queue + scheduler (test)
- [x] Read-only auditor/viewer ditegakkan server-side (test)
- [x] Reset password mandiri (forgot → token → reset) (test)
- [x] POS API idempoten (`client_ref`) — retry sync offline aman (test)
- [x] Command audit integritas `kaza:audit-integrity` (test)
- [x] Rate limit API 60/menit per user (test)
- [ ] UAT tanda tangan owner & Finance (PENDING)

## Keamanan/Data/Deploy/Go-live
- [x] Permission per menu, 403/404/500, tanpa CDN, secret tidak di-log
- [x] Migrate fresh + seed + build + route:list + smoke
- [x] Backup & restore teruji (dump dev → restore DB scratch → 179 tabel + baris kunci identik, 15–16 Okt 2026)
- [ ] HTTPS produksi, kredensial produksi (lihat DEPLOYMENT)
- [ ] Keputusan PENDING_BUSINESS_DECISION disetujui sebelum go-live

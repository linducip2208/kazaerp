# Decisions & Open Questions

## Keputusan arsitektur
- Modular monolith Laravel (bukan microservices); namespace domain `App\Models/<Domain>` + `app/Services` (didokumentasikan di ARCHITECTURE.md).
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

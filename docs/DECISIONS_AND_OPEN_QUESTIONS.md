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
9. Komisi penjualan: tarif & dasar resmi (implementasi saat ini: `company_configs.commission_rate_pct` per entitas; basis = subtotal − diskon; penerima = `customers.sales_owner_id` ?? pembuat invoice; akrual saat invoice lunas, approve Finance).
10. QC/karantina: status default lot GRN (saat ini `released`) & SLA karantina — disarankan `pending` bila QC ketat wajib.
11. Perlakuan akuntansi variance produksi (jurnal variance menunggu metode HPP diputuskan).
12. GPS produksi: provider/perangkat pelacak (arsitektur push API siap; saat ini pengiriman manual dari perangkat driver).
13. Alert kedaluwarsa batch: ambang default 30 hari ke depan + jadwal harian 08:30 + penerima permission `inventory.view` (command `kaza:expiry-alerts --days=N` dapat diubah tanpa deploy) — sesuaikan bila kebijakan FEFO/retur vendor berbeda.

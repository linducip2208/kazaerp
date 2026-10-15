# Product Overview

## Tujuan
Menyatukan operasional banyak perusahaan/brand/unit usaha Kazaka Group & MZA Holdings dalam satu ERP: penjualan, stok, pembelian, keuangan, HR, layanan pet, apparel, thrift, studio, rental.

## Pengguna
Super Admin, Holding Owner, Executive, Entity Admin, Finance (Controller/Staff), HR, Purchasing, Inventory, Branch Manager, Sales, Cashier, Operations, Operator modul, Auditor, Viewer.

## Ruang lingkup (terimplementasi inti)
Organisasi multi-entitas + konteks aktif; RBAC 16 role/60+ permission; CRM + konversi lead; sales order→delivery→invoice→bayar; POS + shift; inventory ledger + reservasi + partial receipt; purchasing PR→PO→GRN→vendor invoice (three-way matching) ; jurnal balance/idempotent/reversal; AR/AP aging; kas; expense; tutup periode; payroll komponen; pet care/vet/grooming/boarding/transport; desain apparel berversi; BOM + WO terkunci; thrift unik + live order; studio; rental anti-bentrok; voucher/loyalty/membership; approval workflow + SoD; laporan + CSV; import CSV bervalidasi; PDF invoice/payslip; PWA aset半年.

## Batasan
- Blueprint PDF (`Blueprint_ERP_Kazaka_Group_MZA_Holdings.pdf`) TIDAK ditemukan di workspace (dicari di root proyek) — mapping kebutuhan disusun dari Master Build Command; berisiko menyimpang dari dokumen resmi.
- Metode HPP: `none` (stok×harga). FIFO/average butuh keputusan Finance.
- Tidak ada integrasi payment gateway, marketplace, WhatsApp/SMS, GPS live — hanya adapter internal + status jujur.
- PWA hanya cache aset `/build/`; tidak ada transaksi offline.
- Konsolidasi antar-entitas: register + laporan per-entitas; eliminasi otomatis BELUM ada (butuh kebijakan Finance).

## Asumsi
Lihat `docs/DECISIONS_AND_OPEN_QUESTIONS.md`.

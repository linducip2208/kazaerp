# Modules

## Identity & Organization (`org.*`, users, roles)
Holding, badan usaha (+kepemilikan), brand, unit bisnis, cabang, lokasi, gudang, departemen, cost/profit center, tahun/periode fiskal, rekening bank, config, dokumen legal. Konteks aktif (holding/entity/cabang) via switcher terotorisasi.

## CRM & Master (`crm.*`, `master.*`)
Customer, supplier, kontak, alamat, produk (+varian, barcode), kategori, satuan, pajak, price list, payment terms, channel, lead source, tags, notes, attachments. Lead→opportunity→konversi anti-duplikat.

## Sales & POS (`sales.*`)
Quotation, SO (+approval+reservasi), DO, invoice (+snapshot, posting otomatis), credit note, retur, deposit, pembayaran partial, komisi, POS + shift + selisih kas.

## Inventory (`inventory.*`)
Bin, batch/lot + expiry + QC/karantina, ledger (sumber kebenaran), saldo, GRN/GI, transfer, reservasi, count, adjustment, serial, kartu stok, alert menipis.

## Purchasing (`purchasing.*`)
PR (+darurat beralasan), RFQ + komparasi, PO (+versi/amendment), partial receipt, QC, vendor invoice + three-way matching (exception→HOLD), deteksi duplikat, retur, evaluasi vendor.

## Finance (`finance.*`)
COA per entitas, jurnal (balance/idempotent/reversal), kas/bank, transfer, rekonsiliasi, expense, petty cash, budget, aturan pajak (konfigurasi), approval jurnal, tutup periode, TB/PL/aging/ledger, register intercompany.

## HR (`hr.*`)
Karyawan, jabatan, riwayat, shift, absensi, cuti + saldo, lembur, periode payroll, komponen configurable, hitung + approval + slip PDF.

## Pet & Transport (`pet.*`)
Owner, pet, janji vet, konsultasi (akses peran), treatment, grooming + antrean, kamar + kalender + log harian, transport order + trip + biaya.

## Apparel & MFG (`mfg.*`)
Koleksi, desain + versi file + approval, BOM versi terkunci di WO, output/reject.

## Thrift & Live (`thrift.*`)
Lot + alokasi biaya, sortir/grade, item unik + foto, sesi live, reservasi anti double-sell, deadline bayar, komisi.

## Studio (`studio.*`), Rental (`rental.*`), Marketing (`marketing.*`)
Proyek + deliverable + approval; katalog + unit fisik + kalender + deposit; campaign + voucher + loyalty + membership.

## Governance (`gov.*`)
Matrix approval, antrean, SoD, histori, audit log.

Permission tiap modul: `<modul>.view/create/update/...` (lihat RolesPermissionsSeeder + USER_ROLES.md).

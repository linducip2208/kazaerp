# Workflows

## Sales-to-cash
Quotation → SO (draft→approved + reservasi) → DO → Invoice (posted + potong stok + jurnal AR/pendapatan) → Payment (partial/paid dari total) → [Credit/Return bila perlu].

## Procure-to-pay
PR (submitted + approval; darurat wajib alasan) → RFQ/vendor quotes → PO (approval; versi saat amend) → GRN (partial ok; status PO partial/received) → Vendor invoice (3-way match; exception→HOLD) → bayar (pending: payment AP UI).

## Stock transfer / adjustment
Transfer antar gudang (kirim→terima, in-transit tercatat); adjustment hanya via approval + alasan; count → selisih → adjustment.

## Service order (grooming/vet)
Booking → check-in (kondisi awal) → checklist/progress → selesai → pickup → invoice → feedback.

## Manufacturing
Desain (versi + approval) → BOM (versi) → WO (BOM terkunci) → issue material → output good/reject/rework → FG receipt.

## Rental lifecycle
Katalog → unit fisik → reservasi (cek bentrok + buffer) → fitting/persiapan → serah terima → kembali → inspeksi → denda/kerusakan → settlement deposit → cleaning.

## Payroll
Periode → komponen → hitung (draft) → approval (terkunci) → slip → (mapping jurnal: pending).

## Finance posting & closing
Sumber (invoice/payment/expense) → jurnal balance → TB/PL → tutup periode (posting ditolak) → buka kembali (terotorisasi + tercatat).

## Intercompany
Transaksi from/to entity → register → laporan; eliminasi manual (otomatis: PENDING_BUSINESS_DECISION).

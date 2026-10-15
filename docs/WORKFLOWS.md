# Workflows

## Sales-to-cash
Quotation → SO (draft→approved + reservasi) → DO → Invoice (posted + potong stok + jurnal AR/pendapatan; harga otomatis mengikuti tier pelanggan bila dikosongkan) → Payment (partial/paid dari total; lunas ⇒ poin loyalty + akrual komisi penjualan) → [Credit/Return bila perlu].

## Procure-to-pay
PR (submitted + approval; darurat wajib alasan) → RFQ/vendor quotes → PO (approval; versi saat amend) → GRN (partial ok; status PO partial/received) → Vendor invoice (3-way match; exception→HOLD) → bayar (pending: payment AP UI).

## Stock transfer / adjustment
Transfer antar gudang (kirim→terima, in-transit tercatat); adjustment hanya via approval + alasan; count → selisih → adjustment.

## Service order (grooming/vet)
Booking → check-in (kondisi awal) → checklist/progress → selesai → pickup → invoice → feedback.

## Manufacturing
Desain (versi + approval) → BOM (versi) → WO (BOM terkunci + snapshot biaya standar material) → issue material (catat biaya aktual) → in_progress → produce (good masuk FG ≤ qty rencana; reject tercatat) → variance = standar − aktual (positif favorable) → completed.

## QC / karantina batch
GRN (isi lot/expired/status QC per baris) → batch dibuat otomatis → review QC di Inventory → Batch & QC → release/quarantine/reject + catatan (audit `quality_checks`) → stok keluar batch diblokir kecuali `released` dan belum kedaluwarsa (guard `StockService`).

## Notifikasi jatuh tempo (queue worker)
Scheduler harian 08:00 → `kaza:overdue-alerts` → dispatch job per invoice lewat jatuh tempo → notifikasi DB ke pemegang permission `sales.view` (idempoten 24 jam; opsi `--sync` menjalankan tanpa worker).

## Rental lifecycle
Katalog → unit fisik → reservasi (cek bentrok + buffer) → fitting/persiapan → serah terima → kembali → inspeksi → denda/kerusakan → settlement deposit → cleaning.

## Payroll
Periode → komponen → hitung (draft) → approval (terkunci) → slip → (mapping jurnal: pending).

## Finance posting & closing
Sumber (invoice/payment/expense) → jurnal balance → TB/PL → tutup periode (posting ditolak) → buka kembali (terotorisasi + tercatat).

## Intercompany
Transaksi from/to entity → register → laporan; eliminasi manual (otomatis: PENDING_BUSINESS_DECISION).

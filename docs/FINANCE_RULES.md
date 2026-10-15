# Finance Rules

- Double-entry: setiap jurnal total debit = kredit (validasi server; selisih > 0.005 ditolak).
- Posting idempotent via `idempotency_key` unik (invoice `inv-{id}`, bayar `pay-{number}`).
- Dokumen posted tidak bisa edit/hapus; koreksi via `PostingService::reverse()` (jurnal lawan + status reversed).
- AR: invoice → payments; pay_status dihitung dari total, bukan dari form. Overpayment ditolak.
- Deposit pelanggan = liabilitas (akun 2200), bukan pendapatan.
- Pajak: konfigurasi (`taxes`, `tax_rules`); tarif contoh PPN 11% pada transaksi — VERIFIKASI ke konsultan pajak sebelum produksi (PENDING).
- HPP: `none` — JANGAN klaim FIFO/average; butuh persetujuan Finance (PENDING_BUSINESS_DECISION).
- Closing: periode tutup menolak posting (dicek di PostingService); buka kembali butuh `finance.close` + alasan tercatat.
- Konsolidasi: dasar per-entitas; eliminasi otomatis BELUM dibangun (PENDING_BUSINESS_DECISION).

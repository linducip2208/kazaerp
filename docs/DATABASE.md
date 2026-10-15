# Database (MySQL 8.4)

- 178 tabel (lihat migrasi `database/migrations/2026_10_15_*.php`). Semua migrasi rerunnable dari DB kosong (`migrate:fresh` hijau).
- Uang `decimal(18,2)`, qty `decimal(18,4)`. Tidak ada float untuk uang.
- Business key unik: `doc_sequences.seq_key`, `*.number` unik per dokumen, `(legal_entity_id, code/sku)` untuk master, `(supplier_id, vendor_ref)` anti duplikat invoice, `live_orders.thrift_item_id` unik, `stock_balances` unik (wh, produk, varian, batch).
- Index pada FK + kolom query (status, tanggal, ref_type/ref_id).
- Status eksplisit (`draft/submitted/approved/posted/paid/partial/hold/matched/exception/...`) + `doc_status_histories`.
- Soft delete hanya master (holdings, entities, brands, units, branches, categories, customers, suppliers, products, employees, owners, pets). Dokumen posted TIDAK boleh dihapus (guard + reversal).
- Kebijakan waktu: Laravel TIMESTAMP (UTC) → tampil Asia/Jakarta (`config/kazaerp.php`, helper `tgl_id()`).
- HPP: `KAZA_COGS_METHOD=none` — valuasi sederhana; FIFO/average PENDING_BUSINESS_DECISION.
- Backup/restore/retensi: `docs/OPERATIONS_RUNBOOK.md`.

## Tambahan v0.4.0
- `transport_positions` (baru): `trip_id`, `lat`/`lng` decimal(10,7), `speed_kmh`, `recorded_at`, `created_by`; index `(trip_id, recorded_at)` — jejak GPS trip transport.
- `work_orders`: `warehouse_id` (nullable), `standard_cost`, `actual_cost`, `variance_amount`, `started_at`, `completed_at`.
- `work_order_lines`: `unit_cost` (biaya standar snapshot saat WO dibuat), `issued_unit_cost` (biaya aktual saat issue) — variance = standard − issued.
- `sales_commissions`: `sales_order_id` kini nullable, + `invoice_id` (nullable), `basis_amount`, `rate_pct`, `approved_by`, `approved_at` — akrual komisi dari invoice tanpa SO.

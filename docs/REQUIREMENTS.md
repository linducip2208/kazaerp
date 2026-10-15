# Requirements

## Functional (ID)
- FR-01 Multi-entitas: holding → legal entity → cabang → gudang; konteks aktif per sesi; isolasi default.
- FR-02 Auth: login/logout, akun nonaktif ditolak, throttle 10/menit, login activity, audit log.
- FR-03 RBAC: 16 role, 60+ permission, middleware `perm`, scope entity/branch.
- FR-04 CRM: lead → opportunity → konversi tanpa duplikasi; follow-up; komplain.
- FR-05 Sales: quotation/SO/DO/invoice/credit/return; snapshot harga; nomor urut aman konkurensi; partial payment; status dari total.
- FR-06 POS: shift buka/tutup + selisih; checkout memotong stok; tanpa shift tidak bisa jual.
- FR-07 Inventory: setiap mutasi → ledger + saldo; reservasi anti-oversell (locking); partial receipt; transfer; count; adjustment via approval.
- FR-08 Purchasing: PR → RFQ → PO (versi) → GRN → vendor invoice; three-way matching (exception→HOLD); deteksi duplikat vendor_ref; retur.
- FR-09 Finance: jurnal wajib balance; idempotent; reversal bukan hapus; periode tutup menolak posting; AR/AP aging; kas; expense approval; budget.
- FR-10 HR: karyawan, absensi, cuti, lembur, payroll dari komponen configurable + approval + slip.
- FR-11 Pet: owner/pet, janji vet, grooming, boarding anti-overcapacity, transport + trip cost.
- FR-12 Apparel/MFG: desain berversi + approval; BOM versi terkunci di WO.
- FR-13 Thrift: item unik; reservasi live anti double-sell.
- FR-14 Rental: unit fisik anti-bentrok termasuk buffer; deposit dicatat bukan pendapatan.
- FR-15 Marketing: campaign, voucher (kuota/periode/minimum), loyalty, membership.
- FR-16 Approval: matrix per doc-type/entitas/level; SoD (pemohon ≠ penyetuju); delegasi tercatat (pending: UI delegasi).
- FR-17 Laporan: filter periode + entitas; export CSV hormat permission.
- FR-18 Import CSV dengan laporan error per baris.
- FR-19 PDF invoice/payslip; print-friendly.
- FR-20 PWA: manifest + SW hanya cache `/build/`.

## Non-functional
- NFR-01 Bahasa Indonesia, timezone Asia/Jakarta, IDR (`idr()`, `tgl_id()`).
- NFR-02 MySQL satu-satunya DB; desimal 18,2 (uang) / 18,4 (qty); FK + unique business key.
- NFR-03 Tanpa CDN; aset lokal via Vite build.
- NFR-04 Error production tanpa stack trace (error pages); secret tidak di-log.
- NFR-05 Test integrasi di MySQL (locking, constraints).

## Acceptance criteria
Lihat `docs/ACCEPTANCE_CHECKLIST.md`.

# Blueprint Traceability — KazaERP (17 Okt 2026)

> Blueprint PDF resmi (`Blueprint_ERP_Kazaka_Group_MZA_Holdings.pdf`) **TIDAK ditemukan**
> di repo maupun workspace (pencarian rekursif 17 Okt 2026: hanya `docs/BLUEPRINT_MAPPING.md`
> + `vendor/.../Blueprint.php` Laravel). Karena itu SEMUA ID requirement di bawah memakai
> prefix **UNVERIFIED-xx** — dipetakan dari Master Build Command §4–§21 (pengganti sementara
> yang sudah dipakai `BLUEPRINT_MAPPING.md`) + verifikasi implementasi nyata di kode.
> Requirement berstatus VERIFIED hanya bila implementasi + tes + bukti acceptance terpenuhi.
> Klaim 100% selesai tanpa bukti DILARANG.

## 1. Struktur holding → entitas → brand → lokasi (terverifikasi di kode)

- Holding → legal entity → branch → warehouse/gudang + konteks sesi `erp_context`
  (`org.*`, `CrudController::baseQuery`, `IsolationTest`). Brand BUKAN otomatis badan usaha
  legal — transaksi selalu memakai `legal_entity_id` pemilik + dimensi brand/lokasi bila ada.
- **PENDING pemilik**: daftar final legal entity & brand; status ZAYAN Style; hubungan
  ZRYN ID/ZRYN Denim; badan usaha, rekening, kapasitas, lokasi tiap brand
  (lihat `DECISIONS_AND_OPEN_QUESTIONS.md` butir 2).

## 2. Matriks requirement (UNVERIFIED = belum bisa diverifikasi ke PDF)

| ID | Ringkasan requirement (dari MBC §) | Modul/rute | Status | Bukti (run nyata) |
|---|---|---|---|---|
| UNVERIFIED-01 | Organisasi, legal entity, brand, cabang, gudang, divisi, konteks (§4) | `org.*`, context switcher | VERIFIED | `IsolationTest`, smoke 200 |
| UNVERIFIED-02 | Login, 2FA, role/permission/scope, audit, throttle (§5) | Auth, `perm`, audit_logs | VERIFIED | `AuthTest`, `Phase5Test` (read-only, reset, backup) |
| UNVERIFIED-03 | CRM: customer/supplier/produk, lead→customer (§6) | `crm.*`, `master.*`, impor | VERIFIED | `BusinessRulesTest`, `Phase8Test` (impor), `ReportsEnterpriseTest` (impor supplier) |
| UNVERIFIED-04 | SO→invoice→bayar, POS+shift, penomoran (§7) | `sales.*` | VERIFIED | `SalesFlowTest`, `Phase6Test` (POS idempoten), `Phase9Test` (**bayar anti double-submit**, sesi ini) |
| UNVERIFIED-05 | Ledger, reservasi, partial, stock opname, adjustment (§8) | `inventory.*` | VERIFIED | `InventoryConcurrencyTest`, `Phase4Test` (karantina/kedaluwarsa guard) |
| UNVERIFIED-06 | PR→PO→GRN→VIN, 3-way match, anti duplikat (§9) | `purchasing.*` | VERIFIED | `PurchasingMatchTest`, `Phase2Test` (bayar utang), `Phase9Test` (**bayar utang idempoten**) |
| UNVERIFIED-07 | COA, jurnal balance/idempoten/reversal, AR/AP, closing, intercompany (§10) | `finance.*` | PARTIAL | `FinancePostingTest`; intercompany register + worksheet manual — eliminasi otomatis **BLOCKED_BY_BUSINESS_DECISION** (butir 4) |
| UNVERIFIED-08 | Karyawan, absensi, payroll configurable (§11) | `hr.*` | PARTIAL | `Phase2Test` (jurnal gaji); pajak/BPJS/UMR configurable — aturan resmi **BLOCKED_BY_BUSINESS_DECISION** (butir 6) |
| UNVERIFIED-09 | Pet, vet, grooming, boarding, transport (§12–13) | `pet.*` | VERIFIED (inti) | `BusinessRulesTest` (overcapacity), `Phase4Test` (GPS); rekam medis hanya `pet.treat` |
| UNVERIFIED-10 | Produk/batch/QC/karantina, harga tier (§14) | master+inventory | VERIFIED | `Phase4Test` (tier, karantina, harga tier API) |
| UNVERIFIED-11 | Koleksi, desain versi, BOM terkunci, WO (§15) | `mfg.*` | PARTIAL | `Phase4Test` (variance); paket demo WO belum ada — modul jalan, smoke 200 |
| UNVERIFIED-12 | Thrift lot, item unik, live order (§16) | `thrift.*` | VERIFIED (inti) | `BusinessRulesTest` (anti double-sell), `Phase3Test` (alokasi biaya) |
| UNVERIFIED-13 | Studio proyek, deliverable, approval (§17) | `studio.*` | PARTIAL | File private + auth dasar; versi file/approval bertingkat belum diuji E2E |
| UNVERIFIED-14 | Rental unit unik, kalender, deposit (§18) | `rental.*` | VERIFIED (inti) | `BusinessRulesTest` (anti-bentrok); deposit = liabilitas |
| UNVERIFIED-15 | Campaign, voucher, loyalty, komisi (§19) | `marketing.*` | PARTIAL | `Phase3Test` (voucher/loyalty), `Phase4Test` (komisi akrual+approve); aturan promo lanjutan menunggu kebijakan |
| UNVERIFIED-16 | Approval matrix, SoD, delegasi, histori (§20) | `gov.*` | VERIFIED | `FinancePostingTest` (SoD), `Phase2Test` (delegasi), `Phase9Test` (**stale approval dibatalkan + pengajuan ulang otomatis**) |
| UNVERIFIED-17 | Dashboard holding/finance/ops/HR + laporan (§21) | `/`, `reports.*` | VERIFIED (inti) | `BusinessRulesTest`, `DemoPerfTest` (dashboard <45 query), `ReportsEnterpriseTest` (8 laporan + CSV/PDF) |

## 3. IN_SCOPE (eksplisit — ERP operasional Kazaka Group/MZA Holdings)

Operasional holding + badan usaha legal yang disahkan + brand/lokasi/divisi di bawahnya:
P0 fondasi (org, user, role, numbering, audit, file, settings), P1 transaksi inti
(CRM, sales, purchase, inventory, finance, HR-inti), P1/P2 per brand YANG DISAHKAN
(Katze Nesia/pet care, produk Katze/Kazaka retail, ZATKE ART, ETZKA/MZA Culture/MZA Kids,
ZRYN + ZAYAN **hanya bila status sah**, Balya Rent, governance/SOP/aset).

## 4. OUT_OF_SCOPE (dilarang tanpa persetujuan tertulis pemilik)

- Fitur SaaS generik di luar operasional holding (multi-tenant publik, marketplace publik,
  billing SaaS, dsb.). Modul repo di luar blueprint: JANGAN dihapus, JANGAN dikembangkan.
- Integrasi eksternal AKTIF (payment gateway, marketplace, WhatsApp/SMS, GPS provider):
  arsitektur siap, kredensial/provider BELUM ADA — klaim "terintegrasi" DILARANG (butir 7).
- Kebijakan yang belum diputuskan: HPP (none), pajak/PPN/e-faktur, payroll (UMK/BPJS/lembur),
  intercompany/eliminasi, deposit sewa & pengakuan pendapatan, komisi (implementasi per
  entitas menunggu pengesahan), QC default lot, variance produksi, ambang expiry —
  semua **BLOCKED_BY_BUSINESS_DECISION**, bukan TODO teknis.
- Tindakan medis/klinis dan keputusan bisnis brand (kapasitas, resep, grading, lot, harga):
  HANYA peran/wilayah berwenang + data yang disahkan; developer DILARANG memutuskan sepihak.

## 5. Perubahan sesi ini (17 Okt 2026, ronde finalisasi)

- `UNVERIFIED-04/06` diperkuat: `sales_payments` + `ap_payments` punya `idempotency_key`
  unik (migrasi `2026_10_17_000001`) — double-submit/retry kunci sama = replay, bukan
  baris/jurnal baru. Menutup risiko R1 FINALIZATION_AUDIT. Bukti: `Phase9Test` 4/29.
- `UNVERIFIED-16` diperkuat: `ApprovalService::invalidatePending()` + `ExpenseController@update`
  — ubah expense setelah pengajuan → approval lama `superseded` + histori, pengajuan baru
  otomatis; ubah setelah disetujui → kembali `draft` + pengajuan ulang. `decide()` atas
  approval kedaluwarsa melempar (sudah diuji).

## 6. Gelombang finalisasi 2 — paket demo 5 modul + scorecard (17 Okt 2026, tanpa migrasi)

- `UNVERIFIED-09` (pet): DemoSeeder kini menanam pemilik+hewan+grooming `queued`+
  boarding `reserved` (idempotent, guard nomor) — modul tetap VERIFIED (inti),
  skor scorecard #10 = 93 (GPS provider tetap BLOCKED butir 12).
- `UNVERIFIED-11` (MFG): WO demo `DEMO-WO-001` completed + variance −10.000 +
  FG +10 + histori 3 tahap — tetap PARTIAL (perlakuan jurnal variance menunggu
  HPP butir 11; WO demo SENGAJA tidak memposting jurnal), skor #11 = 90.
- `UNVERIFIED-12` (thrift): lot+2 item unik + live reserve anti double-sell diuji —
  tetap VERIFIED (inti), skor #12 = 90 (settlement lewat reserve belum E2E).
- `UNVERIFIED-13` (studio): proyek+deliverable v1 di demo + tampil di index —
  tetap PARTIAL (approval bertingkat belum E2E), skor #13 = 71.
- `UNVERIFIED-14` (rental): katalog+unit+reservasi deposit 100.000 + clash ditolak —
  tetap VERIFIED (inti), skor #14 = 94 (kebijakan deposit butir 8).
- Dokumen baru `docs/FEATURE_SCORECARD.md`: 20 fitur × 10 kategori (bobot 100) +
  total + bukti kode/tes + blocker — 11 LULUS 100, 6 HAMPIR, 3 SEBAGIAN, 0 bug kritis.
  Bukti: `Phase10Test` 5/36 + regresi `DemoPerfTest` 4/23.

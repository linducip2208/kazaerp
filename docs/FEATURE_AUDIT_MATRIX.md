# Feature Audit Matrix — KazaERP (17 Okt 2026, ronde finalisasi)

> Sapuan Fakta sesi ini: `href="#"` hanya 2 (dropdown-toggle Bootstrap yang sah, bukan
> tombol palsu); `TODO/FIXME/Coming Soon/Lorem` = 0 di views & app; referensi CDN
> (unpkg/cdnjs/jsdelivr/googleapis) = 0; `route:list` = **386 rute, semua dengan handler,
> 0 Closure**; `composer validate` valid; migrasi dev sinkron (179 tabel + kolom
> `idempotency_key` baru di `sales_payments`/`ap_payments`).

| Area | CRUD+validasi+filter+pagination | Alur & status tervalidasi | Permission/scope/IDOR | Integritas (lock/idempoten/histori/reversal) | Status |
|---|---|---|---|---|---|
| Auth/2FA/user/role | YA | login→2FA→ctx | YA + throttle + sesi nonaktif | audit_logs + login_activities | VERIFIED |
| Org/holding/entity/branch/warehouse | YA | konteks + isolasi | YA (`baseQuery`, guard manual, API guard) | audit | VERIFIED |
| CRM + master + impor CSV | YA + batas 2000 baris + validasi | lead convert anti-duplikat | YA (lead convert guard) | anti-duplikat, audit impor | VERIFIED |
| Sales + invoice + **bayar idempoten** | YA | SO→approve→invoice→pay/partial/paid→retur/refund | YA | lock + sisa + **idempotency_key + replay** + jurnal | VERIFIED |
| POS web/API + shift | YA | shift open→checkout→close + selisih | YA + rate limit | `client_ref` idempoten + ledger | VERIFIED |
| Inventory + batch/QC | YA + kartu stok + aging | GRN→QC→release/quarantine→transfer/adjust | YA | lock + ledger + saldo + guard kedaluwarsa | VERIFIED |
| Purchasing + 3-way + **bayar idempoten** | YA | PR→PO→GRN→VIN→bayar/retur | YA | match HOLD + duplikat guard + **idempotency** + jurnal | VERIFIED |
| Finance (jurnal/AR-AP/bank/expense/budget/closing) | YA | draft→approve→posted→reversal; tutup periode | YA + read-only role | balance + idempoten + reversal + histori | VERIFIED |
| **Expense + approval** | YA | draft→(pending)→approved; **edit→supersede+re-request** | YA | histori + **anti stale approval** (sesi ini) | VERIFIED |
| HR + payroll | YA + slip PDF | draft→approve→jurnal gaji | YA (payroll 403 cashier) | jurnal balance | PARTIAL (aturan pajak/BPJS menunggu) |
| Pet/vet/grooming/boarding/transport | YA | reservasi→checkin→selesai; overcapacity guard | YA (`pet.treat` klinis) | ledger konsumsi + GPS track | VERIFIED (inti) |
| Thrift lot/item/live | YA | lot→item unik→live→jual (anti double-sell) | YA | alokasi biaya + guard unik | VERIFIED (inti) |
| MFG desain/BOM/WO | YA (versi desain) | desain→BOM kunci→issue→produce + variance | YA | snapshot biaya + variance | PARTIAL (demo WO belum ada) |
| Studio | YA (file private) | proyek→deliverable→approval | YA (scoping file) | histori | PARTIAL (versi/approval bertingkat belum E2E) |
| Rental | YA (kalender) | reservasi→pakai→kembali + deposit liabilitas | YA | anti-bentrok + buffer | VERIFIED (inti) |
| Marketing + komisi | YA (voucher/campaign) | apply→pakai→loyalty; akrual→approve komisi | YA | accrual guard lunas | PARTIAL (aturan promo lanjutan menunggu) |
| Governance (matrix/SoD/delegasi/approval) | YA | request→decide + delegasi bertanggal + **supersede** | YA (SoD + delegasi) | histori + lock | VERIFIED |
| Laporan + ekspor/impor | filter + CSV penuh/PDF/cetak + template | rekonsiliasi (bank, budget vs aktual, worksheet) | YA (ekspor = `reports.export`) | CSV anti formula-injection | VERIFIED |
| Dashboard per-peran | KPI + tren 6 bln + exception | — | per-scope (global badge = butir 17 DECISIONS) | tanpa cache lintas-entitas | VERIFIED (84→28 query) |
| API v1 + scheduler/queue/backup | Sanctum + guard entitas + revoke | overdue/expiry alerts + backup harian | rate limit 60/mnt | idempoten + audit-integrity | VERIFIED |

**Legenda**: VERIFIED = implementasi + tes + bukti run nyata ada. PARTIAL = inti jalan +
smoke 200, pendalaman menunggu keputusan/data (bukan rusak). Tidak ada modul berstatus
MISSING di dalam IN_SCOPE; yang di luar scope tercatat di `BLUEPRINT_TRACEABILITY.md` §4.

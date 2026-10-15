# Features (ID, prioritas, status, bukti)

| ID | Fitur | Prioritas | Status | Bukti |
|---|---|---|---|---|
| F-AUTH-01 | Login/logout, akun nonaktif ditolak, throttle | P0 | DONE | AuthController, AuthTest |
| F-AUTH-02 | Konteks entity/cabang terotorisasi | P0 | DONE | switchContext + inScope |
| F-RBAC-01 | 16 role, 60+ permission, middleware | P0 | DONE | RolesPermissionsSeeder, route `perm:` |
| F-RBAC-02 | Isolasi lintas entitas server-side | P0 | DONE | IsolationTest |
| F-ORG-01 | CRUD holding/entity/cabang/gudang | P0 | DONE | org.* E2E 200 |
| F-CRM-01 | CRUD customer/supplier/produk + import CSV | P0 | DONE | ImportController + test validasi |
| F-CRM-02 | Lead convert anti-duplikat | P0 | DONE | BusinessRulesTest |
| F-SAL-01 | SO + numbering aman konkurensi | P0 | DONE | DocNumberService + SalesFlowTest |
| F-SAL-02 | Approve + reservasi stok | P0 | DONE | SalesFlowTest |
| F-SAL-03 | Invoice snapshot + posting + potong stok | P0 | DONE | InvoiceController, E2E verified |
| F-SAL-04 | Partial payment + status dari total | P0 | DONE | pay(), SalesFlowTest |
| F-SAL-05 | Overpayment & oversell ditolak ramah | P0 | DONE | withErrors (fix1) + test |
| F-POS-01 | Shift + checkout + selisih | P1 | DONE | PosController, E2E page 200 |
| F-INV-01 | Ledger + saldo + locking | P0 | DONE | StockService, concurrency test |
| F-INV-02 | Partial receipt PO | P0 | DONE | InventoryConcurrencyTest |
| F-PUR-01 | PR→PO + approval | P0 | DONE | Requisition/PurchaseOrderController |
| F-PUR-02 | Three-way matching + HOLD + duplikat guard | P0 | DONE | PurchasingMatchTest |
| F-FIN-01 | Jurnal balance/idempotent | P0 | DONE | FinancePostingTest |
| F-FIN-02 | Reversal + tutup periode guard | P0 | DONE | FinancePostingTest |
| F-FIN-03 | TB/PL/ledger/aging | P0 | DONE | FinanceReportController, E2E 200 |
| F-FIN-04 | Expense + approval | P1 | DONE | ExpenseController |
| F-HR-01 | Karyawan + absensi | P1 | DONE | hr.* E2E 200 |
| F-HR-02 | Payroll configurable + approval + slip | P1 | DONE | PayrollController (aturan pajak PENDING) |
| F-PET-01 | Owner/pet/vet/grooming | P1 | DONE | pet.* E2E 200 |
| F-PET-02 | Boarding anti-overcapacity | P1 | DONE | BusinessRulesTest |
| F-RNT-01 | Rental anti-bentrok + deposit | P1 | DONE | BusinessRulesTest |
| F-THR-01 | Item unik anti double-sell | P1 | DONE | BusinessRulesTest |
| F-MFG-01 | Desain versi + BOM terkunci | P2 | DONE (inti) | mfg.* E2E 200 |
| F-MKT-01 | Campaign + voucher + loyalty | P2 | DONE (inti) | marketing.* |
| F-GOV-01 | Approval + SoD + histori | P0 | DONE | FinancePostingTest (SoD) |
| F-REP-01 | Laporan + CSV + import validasi | P1 | DONE | Report/ImportController |
| F-UI-01 | Tabler lokal, sidebar per-permission, toast, empty state | P0 | DONE | build 835KB CSS lokal, smoke 22/22 |
| F-PWA-01 | Manifest + SW aset-only | P2 | DONE | public/manifest, sw.js |
| F-PUR-03 | Bayar utang vendor + jurnal | P1 | DONE | ApPaymentController, Phase2Test |
| F-PUR-04 | Retur beli kurangi stok | P1 | DONE | PurchaseReturnController |
| F-HR-03 | Jurnal gaji otomatis (6100→2300) | P1 | DONE | PayrollController, Phase2Test |
| F-GOV-02 | Delegasi approval bertanggal | P2 | DONE | DelegationController, Phase2Test |
| F-GOV-03 | Notifikasi DB + bell | P2 | DONE | NotifyService, NotificationController |
| F-DASH-01 | Dashboard per-peran + tren 6 bln | P1 | DONE | DashboardController, Chart.js lokal |
| F-API-01 | API v1 token + guard entitas | P2 | DONE | routes/api.php, Phase2Test |
| F-OPS-01 | Backup command + scheduler + AdminSeeder | P2 | DONE | kaza:backup (dump+restore teruji), console.php, AdminSeeder |
| F-SEC-02 | Read-only auditor/viewer ditegakkan server-side | P1 | DONE | ReadOnlyRoles, Phase5Test |
| F-AUTH-04 | Reset password mandiri (forgot→token→reset) | P1 | DONE | PasswordResetController, Phase5Test |
| F-SEC-01 | Security headers + cabut sesi nonaktif | P1 | DONE | SecurityHeaders, CheckActiveUser |
| F-AUTH-03 | 2FA TOTP + challenge + QR lokal | P1 | DONE | TwoFactorController, Phase3Test |
| F-FILE-01 | File privat + download terotorisasi + isolasi | P0 | DONE | SecureFileController, Phase3Test |
| F-PET-03 | Rekam medis hanya peran klinis | P0 | DONE | route pet.treat, Phase3Test |
| F-THR-02 | Alokasi biaya lot (rata/proporsional) | P2 | DONE | LotController@allocate, Phase3Test |
| F-MKT-02 | Voucher apply + loyalty accrual | P2 | DONE | VoucherService, Phase3Test |
| F-REP-02 | Stok menua, worksheet intercompany, budget vs aktual, rekonsiliasi bank | P1 | DONE | Report/FinanceReport/BankRecController |
| F-SAL-06 | Harga tier otomatis per pelanggan (retail/wholesale/reseller) | P1 | DONE | PriceService, Phase4Test |
| F-SAL-07 | Komisi penjualan: akrual saat lunas + approve Finance | P2 | DONE | CommissionService, Finance\CommissionController, Phase4Test |
| F-INV-03 | QC/karantina batch + guard keluar (quarantine/expired) | P1 | DONE | BatchController, StockService, Phase4Test |
| F-MFG-02 | Output/variance produksi (standar vs aktual, cap qty) | P1 | DONE | WorkOrderController, Phase4Test |
| F-API-02 | API POS: shift open/close + checkout (harga tier) | P2 | DONE | Api\PosController, PosService, Phase4Test |
| F-API-03 | GPS transport: push posisi + tracking web | P2 | DONE | Api\TransportController, TransportTrackingController, Phase4Test |
| F-OPS-02 | Queue worker: overdue alerts (job + scheduler 08:00) | P2 | DONE | SendOverdueInvoiceAlert, kaza:overdue-alerts, Phase4Test, drain `queue:work` Phase5Test |
| F-DELTA-01 | Eliminasi intercompany otomatis (menunggu kebijakan) | P3 | PENDING | DECISIONS #4 |

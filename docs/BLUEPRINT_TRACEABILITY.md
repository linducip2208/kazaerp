# Blueprint Traceability — KazaERP (17 Okt 2026, ronde blueprint v1.0)

> Sumber kebenaran: `docs/BLUEPRINT_V1_REQUIREMENTS.md` — transkripsi verbatim Blueprint ERP
> Kazaka Group/MZA Holdings v1.0 (14 Okt 2026, dari PDF resmi pemilik). SEMUA ID di bawah adalah
> ID ASLI dari PDF (menggantikan seluruh `UNVERIFIED-xx` ronde sebelumnya).
> Legenda: **VERIFIED** = implementasi + tes + bukti run nyata ada. **VERIFIED (inti)** = alur inti
> jalan + bukti tes, pendalaman menunggu keputusan/data. **PARTIAL** = sebagian jalan, gap eksplisit.
> **MISSING** = belum ada (IN_SCOPE, antre implementasi). **BLOCKED_*** = tak bisa diputus sepihak.
> **OUT_OF_SCOPE** = di luar PDF — dilarang kembangkan.
> Patuh PDF §07/§08/§15: angka/tarif/SLA/contoh jurnal & penomoran = ilustrasi/kerangka, BUKAN kebijakan.

## §01 Prioritas & hasil minimum

| Gelombang PDF | Status repo | Bukti |
|---|---|---|
| P0 fondasi (org, akses, data induk, audit) | VERIFIED | `IsolationTest`, `AuthTest`, `Phase5Test`, `Phase8Test` (impor) |
| P1 inti (jual, bayar, stok, beli, kas/bank) | VERIFIED | `SalesFlowTest`, `Phase6Test`, `Phase9Test`, `PurchasingMatchTest`, `InventoryConcurrencyTest` |
| P1 operasional (pet, custom, apparel, thrift, rental) | VERIFIED (inti) + gap CAT/PRC/INP | `Phase4Test`, `Phase10Test`, `BusinessRulesTest`; gap §RET/CAT/PRC/INP di bawah |
| P2 penguatan (akuntansi, SDM, marketing, konsolidasi) | PARTIAL | Butuh keputusan HPP/pajak/payroll/komisi/eliminasi (butir 3–6,11 `DECISIONS_AND_OPEN_QUESTIONS.md`) |
| P3 optimasi (portal, integrasi lanjutan, analitik) | OUT_OF_SCOPE / BLOCKED_BY_EXTERNAL_DEPENDENCY | Kredensial/provider belum ada (butir 7) |

## §03 Organisasi & cakupan brand — VERIFIED (struktur), PENDING (daftar final)

Holding > legal entity > branch > warehouse + dimensi brand/lokasi/cost center (`org.*`, `CrudController::baseQuery`, `IsolationTest`).
Brand BUKAN badan usaha legal — transaksi selalu `legal_entity_id` pemilik.
**PENDING pemilik**: daftar legal entity & brand final; status **ZAYAN Style** (JANGAN aktifkan); hubungan
**ZRYN ID/ZRYN Denim**; lokasi awal belum validasi fisik (Bekasi Timur/Duren Jaya, Graha Mutiara).
Ekspansi: tutup = nonaktif (bukan hapus) — didukung `is_active`.

## §04 Arsitektur proses & integrasi

| Aturan | Status | Bukti |
|---|---|---|
| Order-to-cash, procure-to-pay, service, MTO, rental, payroll, record-to-report | VERIFIED (alur didukung per modul) | Controllers sales/purchasing/pet/mfg/rental/hr/finance |
| Satu pelanggan N kontak/alamat/hewan/order/invoice/bayar | VERIFIED | Master + sales + pet |
| Satu pembayaran ke N invoice (total ≤ dana sah) | VERIFIED | `SalesFlowTest`, `Phase9Test` (replay, bukan jurnal ganda) |
| Rental & thrift per unit, produk per SKU/batch, hewan per ID | VERIFIED | `BusinessRulesTest` (anti double-sell/bentrok) |
| Sumber/external_id/sync/status + kunci idempoten + webhook retry + tanpa ganda + posting atomik + snapshot | VERIFIED | `idempotency_key` sales/ap (migrasi `2026_10_17_000001`), `client_ref` POS, `Phase9Test`, `Phase6Test` |
| Master tidak mengubah dokumen historis | VERIFIED | Snapshot nama/harga + `guardPosted` + reversal (bukan edit/hapus) |

## §05 Data induk & identitas

| Area | Status | Bukti/cadangan |
|---|---|---|
| Organisasi/pelanggan/hewan/SKU/layanan/vendor/karyawan/aset/akun | VERIFIED | CRUD + `Phase8Test` (impor + anti-duplikat + validasi) |
| Duplikat = saran gabung, histori tetap | VERIFIED | Convert lead menolak duplikat (`master.php:12-27`); **gabung dua pelanggan oleh petugas berwenang** = `CustomerController@merge` + audit, sumber nonaktif + tertaut (histori tidak dipindah) — `Phase12Test` |
| Penomoran usulan `INV-KNI-DJ-202610-00001` | ILUSTRASI — JANGAN terapkan massal | `DocNumberService` berjalan dgn skema sendiri; penomoran ulang dokumen lama = butuh persetujuan (catat keputusan) |
| Urutan batal tidak dipakai ulang | VERIFIED | Nomor dari sequence, batal tidak mengisi ulang |
| Migrasi: template/kolom/kamus/pemilik/cut-off + staging + baris ditolak | PARTIAL | Impor master CSV + template + tolak baris (`ImportController`, `Phase8Test`); **impor transaksional (invoice/PO/mutasi) BLOCKED** (butir 16 DECISIONS) |

## CRM (P1)

| ID | Ringkasan | Status | Bukti (file/tes) |
|---|---|---|---|
| CRM-01 | Lead ditugaskan berdasar brand/jam/kapasitas; pindah owner tercatat | PARTIAL | `owner_id` + convert ada (`LeadController`); **aturan penugasan otomatis + log mutasi owner MISSING** |
| CRM-02 | Pipeline terpisah per jenis + definisi keluar tiap tahap | PARTIAL | Stage lead + opportunity + konversi ada; **definisi keluar formal per pipeline MISSING** |
| CRM-03 | Jadwal follow-up + pengingat + hasil + eskalasi lead terlantar | VERIFIED | `Crm/FollowupController.php` (jadwal/complete/escalate + lock + audit + notifikasi), `kaza:followup-reminders` + `SendFollowupReminder` (dedupe 24 jam, scheduler 08:15), views `modules/crm/followups/*`, `Phase11Test` 2/14 |
| CRM-04 | Penawaran berversi (masa berlaku, rincian, syarat, approval diskon) | VERIFIED (inti) | `Sales/QuotationController.php` (valid_until wajib ≥ tanggal, approve tolak kedaluwarsa, snapshot `quotation_versions` per approve), views form/show + arsip versi, `Phase12Test` |
| CRM-05 | Deteksi duplikat + penggabungan oleh petugas berwenang | VERIFIED | Convert menolak duplikat nama/telepon; merge `Master/CustomerController@merge` (perm `crm.convert`, 1 entitas, sumber nonaktif + `merged_into_customer_id`, histori dipertahankan, audit) + form show; `Phase12Test` |
| CRM-06 | Riwayat lintas brand sesuai akses; opt-out promosi | VERIFIED | Isolasi entitas + peran lintas (`IsolationTest`); `customers.marketing_opt_out` + kolom Promo + `Marketing/CampaignController@show` (penerima = aktif − opt-out − gabungan, pengecualian dihitung); `Phase12Test` |
| Kontrol CRM | CS tak sahkan bayar tanpa bukti; ekspor dibatasi; pesan via alur | VERIFIED | `perm:*` + `ReadOnlyRoles` + ekspor `reports.export` (`Phase5Test`) |

## SAL (P1)

| ID | Ringkasan | Status | Bukti |
|---|---|---|---|
| SAL-01 | Order campuran barang/jasa, paket, DP, bertahap, split tender | VERIFIED (inti) | `SalesOrderController` + lines + DP; **split tender formal PARTIAL** |
| SAL-02 | Reservasi stok + kedaluwarsa mengembalikan ketersediaan | VERIFIED | Reservasi + expiry (`Phase6Test` POS, inventory) |
| SAL-03 | Harga tier + periode; ubah manual diaudit | VERIFIED | `PriceService` tier + audit (`Phase4Test`) |
| SAL-04 | Bayar pending/verified/failed/reversed; bukti ≠ verifikasi | VERIFIED | Status + `idempotency_key` + replay (`Phase9Test`) |
| SAL-05 | Tutup shift cocokkan sistem/fisik/nontunai/setoran/selisih | VERIFIED | `PosController` shift open/close + selisih (`Phase6Test`) |
| SAL-06 | Cetak/kirim invoice; lunas hanya bila alokasi sah | VERIFIED | Invoice + alokasi ≤ tagihan (`SalesFlowTest`) |
| Kontrol SAL | Batal tak hapus histori; posted via kredit/pengembalian; lebih bayar = kredit pelanggan | VERIFIED | `guardPosted` + credit note + retur (`SalesFlowTest`) |

## RET (P1)

| ID | Ringkasan | Status | Bukti |
|---|---|---|---|
| RET-01 | Tiket tertaut transaksi/barang/hewan/lokasi/petugas + nomor pelacakan | VERIFIED | `Crm/ComplaintController.php` (related_type/id, tracking `CMP-xxxxxx`), `Phase11Test` |
| RET-02 | Urgensi → eskalasi; insiden hewan → penanggung jawab klinis | VERIFIED (alur) | Urgensi + review/assignee + critical diurutkan; **routing otomatis ke peran klinis PARTIAL** (assignee manual) |
| RET-03 | Retur diterima di lokasi pemeriksaan; stok tersedia setelah QC | PARTIAL | `SalesReturnController` + approve ada; **gating QC sebelum available MISSING** (berlaku umum via batch QC, bukan per retur) |
| RET-04 | Refund sebagian/penuh via referensi asli + rekening terverifikasi | PARTIAL | Refund via credit note/pembayaran + approval; **verifikasi rekening independen MISSING** |
| RET-05 | Keputusan = perbaikan/penggantian/kredit/refund/penolakan beralasan | VERIFIED | `decision` + resolution wajib + SoD pelapor≠penyelesai (`ComplaintController::resolve/reject`, `Phase11Test`) |
| RET-06 | Voucher/poin/komisi dikoreksi dari nilai bersih pasca-batal | PARTIAL | Komisi akrual saat lunas + approve (`Phase4Test`); **koreksi otomatis pasca-retur MISSING** (manual via adjustment) |
| Kontrol RET | Pemohon refund ≠ penyetuju final; refund ≤ saldo; dokumen koreksi | VERIFIED | SoD tiket + approval finance + lock (`Phase11Test`, `FinancePostingTest`) |

## CAT (P1) — **MISSING sebagai alur** (kredit parsial: master hewan)

| ID | Status | Keterangan |
|---|---|---|
| CAT-01..06 | MISSING | Registrasi + foto + pindah kandang + karantina + observasi harian + clearance + reservasi berbatas + serah terima **belum ada modul cattery**; yang ada hanya master `Pet`/`PetOwner` generik. Jangan kembangkan sebelum brand/lokasi cattery disahkan pemilik. |

## GRM (P1)

| ID | Status | Bukti |
|---|---|---|
| GRM-01 Booking slot anti-bentrok | VERIFIED (inti) | `GroomingOrderController` + guard overcapacity (`BusinessRulesTest`) |
| GRM-02 Form penerimaan | VERIFIED (inti) | Form + SVC; pendalaman foto bawaan PARTIAL |
| GRM-03 Hold bila perlu dokter | PARTIAL | **Kaitan formal grooming→vet hold MISSING** |
| GRM-04 Tambahan perlu persetujuan pelanggan | VERIFIED (inti) | Persetujuan tersimpan (alur); bukti tanda digital PARTIAL |
| GRM-05 Checklist + foto + QC wajib | VERIFIED (inti) | Checklist + QC status |
| GRM-06 Konsumsi bahan kurangi stok | VERIFIED | Ledger konsumsi (`Phase4Test`) |

## VET (P1)

| ID | Status | Bukti |
|---|---|---|
| VET-01 Rekam medis + dokter + waktu | VERIFIED (inti) | `VetAppointmentController` + `Consultation` |
| VET-02 Diagnosis hanya peran klinis | VERIFIED | `perm:pet.treat` (`Phase3Test`) |
| VET-03 Tindakan perlu persetujuan + versi formulir | PARTIAL | Approval umum ada; **persetujuan per kategori + versi formulir MISSING** |
| VET-04 Resep tertaut batch + guard dispensing | PARTIAL | Resep ada; **guard batch + blokir dispensing pra-sah MISSING** |
| VET-05 Lampiran + koreksi = addendum | PARTIAL | File privat + auth (`SecureFileController`); **addendum (bukan edit) MISSING** |
| VET-06 Kontrol/rujukan + instruksi | PARTIAL | Jadwal kontrol ada; **status tindak lanjut formal PARTIAL** |
| Kontrol VET | VERIFIED | CS lihat tagihan saja (scope perm) |

## INP (P1) — **MISSING sebagai alur** (ranap)

| ID | Status | Keterangan |
|---|---|---|
| INP-01..06 | MISSING | Zona kandang/ kapasitas/ pindah tercatat/ instruksi berversi/ obat terjadwal-aktual/ observasi-eskalasi/ discharge clearance **belum ada modul ranap** (boarding hanya untuk hotel). Antre implementasi setelah kapasitas & SOP klinik disahkan. |

## HOT (P1)

| ID | Status | Bukti |
|---|---|---|
| HOT-01 Kalender kapasitas + jeda sanitasi | VERIFIED (inti) | `BoardingReservationController` + room + anti-bentrok (`BusinessRulesTest`); **jeda sanitasi otomatis PARTIAL** |
| HOT-02 Check-in kondisi + foto + syarat + titipan | VERIFIED (inti) | `BoardingCheckinController` |
| HOT-03 Makan/kebiasaan tampil di tugas shift | PARTIAL | Data tersimpan; **tugas per shift otomatis MISSING** |
| HOT-04 Konsumsi/aktivitas/kondisi tercatat | PARTIAL | Catatan observasi dasar; **log harian formal PARTIAL** |
| HOT-05 Perpanjang cek tabrakan + tagihan | VERIFIED (inti) | Guard bentrok + tagihan |
| HOT-06 Check-out cocokkan barang/kondisi/bayar/penjemput | VERIFIED (inti) | Alur check-out + pembayaran |
| Kontrol HOT | VERIFIED (inti) | Kamar tersedia kembali pasca-sanitasi (status; otomasi PARTIAL) |

## PRC (P1) — **MISSING sebagai alur** (room care)

| ID | Status | Keterangan |
|---|---|---|
| PRC-01..06 | MISSING | Survei/penawaran/jadwal tim-alat/checklist/biaya/jadwal berulang **belum ada modul room care**. Antre setelah layanan & tarif disahkan. |

## STD (P1)

| ID | Status | Bukti |
|---|---|---|
| STD-01 Booking studio/alat + persiapan | VERIFIED (inti) | `Studio/ProjectController` + kalender (inti) |
| STD-02 Brief konsep/referensi | VERIFIED (inti) | Brief + lampiran privat |
| STD-03 Seleksi pelanggan + batas waktu | PARTIAL | Deliverable ada; **daftar terkonfirmasi + tenggat MISSING** |
| STD-04 Editor + tugas berversi + tenggat | PARTIAL | **Penugasan editor formal MISSING** |
| STD-05 Revisi dibatasi; tambahan = penawaran | PARTIAL | Versi deliverable v1 (`Phase10Test`); **batas revisi + change order MISSING** |
| STD-06 Delivery tautan terbatas + masa akses | VERIFIED (inti) | File privat + auth + masa akses (SecureFile) |
| Kontrol STD | PARTIAL | Versi tidak menimpa (v1 dipertahankan); **isolasi file per pihak PARTIAL** |

## TRN (P1)

| ID | Status | Bukti |
|---|---|---|
| TRN-01 Order + titik + instruksi hewan | VERIFIED | `TransportOrderController` |
| TRN-02 Jadwal + kapasitas + jeda + rute | VERIFIED (inti) | Penjadwalan + durasi; rute aktual via GPS push |
| TRN-03 Checklist kendaraan/kandang/kondisi | PARTIAL | **Checklist pra-berangkat formal MISSING** |
| TRN-04 Status + waktu + pelaksana | VERIFIED | Status + tracking (`TransportTrackingController`, `Phase4Test` GPS) |
| TRN-05 Serah terima + eskalasi insiden | VERIFIED (inti) | Serah terima + penerima sah |
| TRN-06 BBM/tol/parkir/vendor/km/servis | PARTIAL | Biaya tercatat; **kaitan servis→blokir alokasi MISSING** |
| Kontrol TRN | VERIFIED (inti) | Bukti wajib/pengecualian atasan; guard ganda slot (inti) |

## PET (P1)

| ID | Status | Bukti |
|---|---|---|
| PET-01 SKU per varian | VERIFIED | Varian + SKU unik (`Phase4Test`) |
| PET-02 Batch + karantina diblokir jual | VERIFIED | QC/karantina + guard (`Phase4Test`) |
| PET-03 FEFO | VERIFIED | Ambil kedaluwarsa terdekat (`Phase4Test`) |
| PET-04 Harga & MOQ reseller/grosir + limit | VERIFIED | Tier + limit kredit (inti) |
| PET-05 Dropship identitas + bukti | PARTIAL | **Identitas pengirim dropship MISSING** |
| PET-06 Lacak batch (sisa/pakai/penerima) | VERIFIED | Kartu stok + batch (`Phase4Test`) |

## ART (P1 custom)

| ID | Status | Bukti |
|---|---|---|
| ART-01 Brief + versi + ringkasan | VERIFIED | `DesignController` + `DesignVersion` |
| ART-02 Syarat DP + kunci produksi | PARTIAL | DP tercatat; **gate rilis formal MISSING** |
| ART-03 Routing per PIC | VERIFIED (inti) | WO routing |
| ART-04 Kapasitas & tenggat + bertahap | VERIFIED (inti) | WO + kirim bertahap |
| ART-05 Rework + penanggung biaya | PARTIAL | Variance tercatat (`Phase4Test`); **penanggung biaya formal PARTIAL** |
| ART-06 QC vs brief + lunas/rilis | VERIFIED (inti) | QC + status |
| Kontrol ART | VERIFIED | Ubah pasca-approve = change order (supersede); DP ≠ laba (deposit = kewajiban) |

## APP (P1 apparel)

| ID | Status | Bukti |
|---|---|---|
| APP-01 Varian relevan; polos vs cetak | VERIFIED | Varian produk |
| APP-02 BOM berversi, dikunci saat rilis | PARTIAL | BOM ada; **kunci versi saat rilis MISSING** |
| APP-03 Family set dipecah per anggota | PARTIAL | **Pecah komponen family MISSING** (demo family L/M/anak belum ada) |
| APP-04 File cetak depan/belakang + approval | PARTIAL | File privat; **penanda posisi + anti-tertukar MISSING** |
| APP-05 Produksi baik/reject/rework/sisa + jasa vendor | VERIFIED | WO issue/produce + variance (`Phase4Test`) |
| APP-06 Cek bahan & kapasitas sebelum janji | PARTIAL | Stok terlihat; **janji tanggal terkunci kapasitas MISSING** |

## THR (P1 thrift)

| ID | Status | Bukti |
|---|---|---|
| THR-01 Bal/lot + alokasi disetujui finance | VERIFIED (inti) | `LotController::allocate` (`Phase3Test`) |
| THR-02 Item unik + kondisi pra-jual | VERIFIED | `ItemController` unik |
| THR-03 Laundry/perbaikan tambah biaya | VERIFIED (inti) | Biaya item |
| THR-04 Live host/slot/daftar/harga/hasil | VERIFIED (inti) | `LiveSession` + order |
| THR-05 Reservasi berbatas bayar | VERIFIED | `LiveOrderController::reserve` anti double-sell (`BusinessRulesTest`, `Phase10Test`) |
| THR-06 Packing pindai kode; paket usaha | PARTIAL | **Pindai packing + aturan paket MISSING** |
| Kontrol THR | VERIFIED | 1 unit 1 reservasi sah; retur diperiksa ulang (alur) |

## RNT (P1 rental)

| ID | Status | Bukti |
|---|---|---|
| RNT-01 Kalender blokir + fitting + laundry | VERIFIED (inti) | Kalender + buffer (`ReservationController`) |
| RNT-02 Booking unit/durasi/DP/deposit/syarat | VERIFIED | `RentalBookingController` + deposit 100rb demo (`Phase10Test`) |
| RNT-03 Checklist keluar/kembali + foto | VERIFIED (inti) | Checklist + kondisi |
| RNT-04 Perpanjang sah bila tak tabrakan | VERIFIED | Guard tabrakan |
| RNT-05 Rusak/telat = bukti + hitung + kebijakan | PARTIAL | **Hitung denda + bukti formal PARTIAL** |
| RNT-06 Deposit direkonsiliasi; unit ditahan sampai layak | PARTIAL | Deposit = liabilitas; **rekonsiliasi pengembalian/potongan + tahan unit PARTIAL** |
| Kontrol RNT | VERIFIED | Deposit ≠ pendapatan; ganti unit tercatat; potongan ditinjau |

## INV (P1)

| ID | Status | Bukti |
|---|---|---|
| INV-01 Status on-hand/reserved/available/quarantine/damaged/in-transit | PARTIAL | Ledger + quarantine/damaged didukung; **in-transit terpisah MISSING** |
| INV-02 Ledger tiap mutasi; saldo bukan edit bebas | VERIFIED | `StockLedger` + lock + `kaza:audit-integrity` (`InventoryConcurrencyTest`) |
| INV-03 Mutasi 2 langkah + exception selisih | PARTIAL | `StockTransfer` kirim+terima; **exception selisih formal PARTIAL** |
| INV-04 Konversi satuan + expiry terbawa | PARTIAL | `Uom` ada; **konversi terkunci + expiry наслед PARTIAL** |
| INV-05 Opname cut-off + blind count | VERIFIED (inti) | `StockCount` + lines |
| INV-06 Adjustment + alasan + approve + dampak | VERIFIED | Adjustment + ambang + histori |
| Kontrol INV | VERIFIED | Negatif diblokir; backdate pasca-tutup ditolak; konsinyasi terpisah (inti) |

## PUR (P1)

| ID | Status | Bukti |
|---|---|---|
| PUR-01 PR tujuan/pusat biaya/urgensi/budget | VERIFIED | `RequisitionController` |
| PUR-02 Perbandingan penawaran | VERIFIED (inti) | `VendorQuote`/`Rfq` |
| PUR-03 PO versi + terima sebagian | VERIFIED | `PurchaseOrderController` + versi |
| PUR-04 3-way match PO–terima–tagih; jasa = BA | VERIFIED | Match + HOLD selisih (`PurchasingMatchTest`) |
| PUR-05 Lebih harga/qty = antrean pengecualian | VERIFIED | HOLD + toleransi |
| PUR-06 Vendor dinilai; ubah rekening verifikasi terpisah | PARTIAL | Retur + histori ada; **penilaian vendor + verifikasi rekening MISSING** |
| Kontrol PUR | VERIFIED | Jalur darurat + review; duplikat invoice terdeteksi (kunci); pemohon tunggal tak sahkan bayar |

## FIN (P1)

| ID | Status | Bukti |
|---|---|---|
| FIN-01 Terima dialokasi ke invoice/DP/kredit | VERIFIED | Alokasi + sisa (`SalesFlowTest`, `Phase9Test`) |
| FIN-02 Jadwal bayar + prioritas + otorisasi | PARTIAL | Aging + jatuh tempo; **jadwal bayar terpusat PARTIAL** |
| FIN-03 Rekonsiliasi bank; unmatched ditindaklanjuti | VERIFIED | `BankRecController` + unmatched |
| FIN-04 Kas kecil limit/pemegang/bukti/saldo/replenish | PARTIAL | `CashAccount` + kas; **limit + replenish formal PARTIAL** |
| FIN-05 AR aging/limit/reminder/dispute; hapus otorisasi | VERIFIED (inti) | Aging + `kaza:overdue-alerts` + hapus terkontrol |
| FIN-06 AP invoice + DP vendor + kredit nota + parsial | VERIFIED | `ApPaymentController` idempoten (`Phase9Test`) |
| Kontrol FIN | VERIFIED | Maker-checker + unidentified penampung; bukti ≠ rekonsiliasi |

## ACC (P1)

| ID | Status | Bukti |
|---|---|---|
| ACC-01 Posting map debit = kredit | VERIFIED | Balance guard (`FinancePostingTest`) |
| ACC-02 Pendapatan/DP/deposit/stok/HPP/aset/beban dibedakan | VERIFIED (implementasi; kebijakan = ilustrasi) | DP/deposit = kewajiban; HPP `none` menunggu putusan (butir 3) |
| ACC-03 Rekonsiliasi kontrol ↔ subledger | VERIFIED (inti) | Laporan + worksheet |
| ACC-04 Biaya bersama + dasar + versi periode | PARTIAL | Worksheet manual; **alokasi otomatis + versi MISSING** |
| ACC-05 Intercompany pasangan + timbal balik + selisih | PARTIAL | `IntercompanyTxn` register; **eliminasi otomatis BLOCKED_BY_BUSINESS_DECISION** (butir 4) |
| ACC-06 Tutup kunci; konsolidasi mapping + eliminasi | PARTIAL | Period close + lock VERIFIED; **konsolidasi eliminasi BLOCKED** (butir 4) |
| Kontrol ACC | VERIFIED | Jurnal final = reversal; buka periode = approval + jejak; pajak ditinjau kompeten (tarif BLOCKED butir 5) |

## HRM (P1)

| ID | Status | Bukti |
|---|---|---|
| HRM-01 Permintaan terstruktur + budget + approval | MISSING | **Modul rekrutmen (permintaan) belum ada** |
| HRM-02 Pipeline screening/interview/tes/keputusan | MISSING | **Pipeline rekrutmen belum ada** |
| HRM-03 Onboarding dokumen/training/akun/aset/PIC | PARTIAL | Employee CRUD + user + aset tercatat; **checklist onboarding MISSING** |
| HRM-04 Penempatan & kontrak histori; lintas brand tak timpa | VERIFIED | Histori penempatan + 1 ID |
| HRM-05 Absensi/izin/cuti/koreksi via approval; jadwal berversi | VERIFIED | `AttendanceController` + `LeaveRequest` + approval |
| HRM-06 Offboarding cabut akses + rekonsiliasi aset + serah terima | PARTIAL | Nonaktif cabut sesi (`Phase5Test`); **rekonsiliasi aset + serah terima formal PARTIAL** |
| Kontrol HRM | VERIFIED | Data sensitif terpisah (`hr.salary` vs `hr.view`) |

## PAY (P1)

| ID | Status | Bukti |
|---|---|---|
| PAY-01 Komponen tetap/variabel + efektif + prorata | VERIFIED (inti) | `PayrollComponent` + periode + jurnal gaji (`Phase2Test`) |
| PAY-02 Lembur & koreksi pasca-setuju | PARTIAL | `Overtime` ada; **kaitan approval→hitung PARTIAL** |
| PAY-03 Komisi basis jelas (lunas/bersih, retur, tunggu) | VERIFIED (implementasi; kebijakan pending) | Akrual saat lunas + approve finance (`Phase4Test`); basis = subtotal−diskon, per entitas (butir 9); **adjustment retur periode berikut PARTIAL** |
| PAY-04 Preview beda bulan lalu + data hilang + tak wajar | PARTIAL | **Preview perbandingan MISSING** |
| PAY-05 Approve kunci batch; file bayar & slip terbatas | VERIFIED | Kunci + slip PDF + `hr.salary` guard |
| PAY-06 KPI formula/target/bobot/sumber/keberatan/rencana | MISSING | **Modul KPI belum ada** |
| Kontrol PAY | VERIFIED | Tarif konfigurasi; pembuat ≠ satu-satunya penyetuju; batch/ref unik |

## MKT (P1)

| ID | Status | Bukti |
|---|---|---|
| MKT-01 Kalender lintas brand | PARTIAL | Campaign list; **kalender formal MISSING** |
| MKT-02 Brief tujuan/audiens/pesan/CTA/visual | PARTIAL | Field dasar; **brief terstruktur + versi aset PARTIAL** |
| MKT-03 KOL rate/deliverables/kontrak/bukti | MISSING | **Modul KOL belum ada** |
| MKT-04 Campaign biaya media/produksi/talent/sampel/fee | PARTIAL | Budget; **rinci biaya PARTIAL** |
| MKT-05 Voucher/UTM/referral + atribusi tunggal | PARTIAL | Voucher kuota/pakai (`Phase3Test`); **atribusi model + bobot 100% MISSING** |
| MKT-06 Affiliate saldo pending/eligible/paid + koreksi | MISSING | **Saldo affiliate belum ada** (`Membership` hanya tier) |
| Kontrol MKT | VERIFIED (prinsip) | ROAS ≠ laba dilabeli; metrik ber-tanggal+sumber (laporan) |

## LOY (P1)

| ID | Status | Bukti |
|---|---|---|
| LOY-01 Program kriteria/manfaat/biaya/validitas | VERIFIED (inti) | `Membership` + voucher periode |
| LOY-02 Voucher kode/kuota/minimum/periode/kombinasi | VERIFIED | `VoucherController` + guard kuota (`Phase3Test`) |
| LOY-03 Poin ≠ tunai; ledger beri/pakai/expiry/koreksi | PARTIAL | `loyalty_points` ada; **aliran pakai/expiry/koreksi PARTIAL** |
| LOY-04 Reminder + rekomendasi medis dokter | MISSING | **Reminder loyalty + rekomendasi medis MISSING** |
| LOY-05 Segmentasi repeat/dormant/bernilai | MISSING | **Segmentasi belum ada** |
| LOY-06 Lintas brand + penanggung + settlement | MISSING | **Settlement internal loyalty belum ada** |
| Kontrol LOY | VERIFIED (inti) | 1 voucher 1 tebus konkuren (lock); retur→koreksi (alur); opt-out ditegakkan via `marketing_opt_out` + daftar penerima kampanye (`CampaignController@show`, `Phase12Test`) |

## GOV (P1)

| ID | Status | Bukti |
|---|---|---|
| GOV-01 SOP nomor/versi/penyetuju/berlaku/bukti baca | MISSING | **Modul SOP belum ada** |
| GOV-02 Tugas rutin otomatis + PIC/tenggat/checklist/eskalasi | PARTIAL | Scheduler commands (backup/overdue/expiry/followup); **tugas generik + eskalasi MISSING** |
| GOV-03 Checklist kritis + bukti; pengecualian berwenang | PARTIAL | QC grooming/produksi + approval; **checklist generik MISSING** |
| GOV-04 Aset lokasi/pemegang/kondisi/servis/serah terima | MISSING | **Register aset belum ada** |
| GOV-05 Kontrak klasifikasi/akses/akhir/pengingat | MISSING | **Modul kontrak belum ada** |
| GOV-06 Insiden kronologi/dampak/akar/tindakan/verifikasi | MISSING | **Insiden internal belum ada** (komplain eksternal ≠ insiden; lihat RET) |
| Kontrol GOV | VERIFIED (parsial) | Approval matrix + SoD + delegasi + histori (`FinancePostingTest`, `Phase2Test`, `Phase9Test`) |

## §06 Hak akses — VERIFIED

Matrix peran + pisah lihat/buat/ubah/approve/posting/bayar/ekspor/konfigurasi/batal; lintas brand eksplisit;
lampiran ikut izin induk; akun individual; MFA sensitif; emergency + expiry + review; nonaktif cabut sesi/token.
Bukti: `RolesPermissionsSeeder`, `perm` middleware, `ReadOnlyRoles`, `Phase5Test`, `IsolationTest`, `AuthTest`.

## §07 Persetujuan & SLA

Mesin status + ubah pasca-approve batalkan persetujuan (`invalidatePending`, `Phase9Test`) + delegasi
berperiode + ditolak = revisi berhistori — **VERIFIED**.
**Nominal & SLA = kerangka, BUKAN kebijakan berlaku** — menunggu penetapan manajemen (BLOCKED).

## §08 Posting ilustratif — BUKAN kebijakan final

DP/deposit/kredit/HPP/susut/tutup didokumentasikan sebagai ilustrasi; implementasi mengikuti perilaku yang
sudah diuji (deposit/DP = kewajiban, bukan pendapatan — `Phase10Test`). HPP/pajak/payroll/komisi/eliminasi/
pengakuan = **ilustratif saja**, menunggu pengesahan (butir 3–6,11).

## §09 KPI owner — PARTIAL (provisional, target menunggu baseline)

Pendapatan bersih, laba kotor (N/A bila denominator 0), laba operasional brand, kas rekonsiliasi,
lead-to-paid, repeat, occupancy, on-time, CAC/ROAS, aging, tenaga kerja — tersedia di dashboard/laporan
sebagai angka provisional berlabel; target vs aktual + ekspor berfilter ada (`ReportsEnterpriseTest`).

## §10 Kamus status — VERIFIED (inti)

Order/Payment/Reservation/Stock/Medical/Rental/Journal/Approval/Attachment + lintas-objek
(created_by/versi/organisasi/sumber/external_id/WIB/desimal/telepon) ditegakkan di controller + guard
transisi (`setStatus`, `guardPosted`, `Phase11Test` histori). Validasi invoice lintas entitas + reservasi
saat simpan + lampiran pra-transisi — VERIFIED (inti).

## §11 Formulir standar — PARTIAL

Terima hewan/observasi/WO/brief/QC/serah terima/PR/terima barang/expense/opname tercakup via CRUD +
approval + audit + tanda versi; **objek formulir khusus + bukti tanda terstruktur PARTIAL**.

## §12 NFR — PARTIAL (target negosiasi, bukan janji layanan)

HTTPS/hash/MFA/izin/secrets (config) + audit pelaku-waktu-sebelum-sesudah + samarkan sensitif +
backup terenkripsi + scheduler + ekspor/impor + idempotency/ retry + runbook — VERIFIED (inti).
RPO≤24j/RTO≤8j + p95≤3dtk + load baseline + uptime kontrak + uji restore berkala — **PARTIAL**
(restore terverifikasi `Phase5Test`; target produksi menunggu infrastruktur/keputusan).

## §13 Implementasi bertahap — diikuti

Gelombang 0–5 tercermin di repo (fondasi → pilot inti → operasional → penguatan).
Migrasi 8 langkah + saldo pembuka + cutover/rollback — PARTIAL (prosedur cutover formal menunggu pemilik).

## §14 UAT kunci

| ID | Uji | Status | Bukti |
|---|---|---|---|
| UAT-01 | DP/pelunasan/refund sebagian tepat | PARTIAL | `SalesFlowTest`; **s
...[truncated 3881 chars]
# Demo Guide — KazaERP (17 Okt 2026)

Demo = instalasi ulang yang AMAN + skenario end-to-end yang bisa diverifikasi.
Bukan sekadar akun di README: seeder membangun master + transaksi nyata
(jurnal balance, ledger stok, approval, pembayaran) yang tampil di dashboard/laporan.

## 1. Batasan keamanan (baca dulu)
- Demo HANYA untuk database dev/test khusus (`kazaerp`, `kazaerp_testing`).
  `DemoSeeder` + `demo:seed` **menolak berjalan di `APP_ENV=production`**.
- Pengecualian pemilik: set `DEMO_ENABLED=true` + `demo:seed --force` — tetap
  disarankan HANYA di database terisolasi, read-mostly, rate-limited; pengunjung
  tidak boleh mendapat role admin.
- Hint kredensial di layar login **disembunyikan otomatis di production**
  (`login.blade.php` + `config/kazaerp.php: demo_enabled`).
- Tidak ada route reset publik. Reset = jalankan ulang `demo:seed` (idempotent)
  oleh operator, bukan pengunjung.

## 2. Setup database demo
```powershell
# 1) Pastikan .env menunjuk DB demo (JANGAN produksi), lalu:
php artisan migrate --force
php artisan demo:seed
# atau: php artisan "db:seed" "--class=Database\Seeders\DemoSeeder"
```
Ulangi perintah yang sama untuk reset — tidak menggandakan data
(guard nomor `DEMO-*` + `idempotency_key` jurnal `demo-*`).

## 3. Akun demo (password akun demo: `Demo#2026`; admin: `Kazaka#2026`)
| Email | Role | Untuk mencoba |
|---|---|---|
| `admin@kazaka.local` | super_admin | Semua modul, dashboard holding |
| `finance.demo@kazaka.local` | finance_controller | Jurnal, approve, bayar, aging |
| `gudang.demo@kazaka.local` | inventory_manager | GRN, stok, batch |
| `kasir.demo@kazaka.local` | cashier | POS/kasir, shift |
| `hr.demo@kazaka.local` | hr_manager | Karyawan, absensi, payroll |
| `viewer.demo@kazaka.local` | viewer | Read-only lintas modul |

Login di `http://kazaerp.test/login` (dev). Semua akun non-aktif di produksi
(tidak dibuat oleh `AdminSeeder` produksi yang memakai `KAZA_ADMIN_*`).

## 4. Skenario verifikasi (data `DEMO-*` di entitas MZA-HOLD)
1. **Sales**: SO `DEMO-SO-001` (approved, 5× @75rb) → invoice `DEMO-INV-001`
   (2× @75rb + PPN 11% = 166.500, `paid`) → jurnal `J-DEMO-INV-001` balance →
   pelunasan `DEMO-PAY-001` → jurnal `J-DEMO-PAY-001` balance.
   Cek: Sales Order → Invoice → jurnal `invoice`/`sales_payment`; stok DEMO-001
   berkurang 2 (kartu stok `invoice`), reservasi SO tersisa 3.
2. **Purchase**: PO `DEMO-PO-001` (`received`, approval diputus finance — SoD) →
   GRN `DEMO-GRN-001` (10 masuk) → vendor invoice `DEMO-VIN-001` (`matched`,
   555.000) → bayar parsial `DEMO-AP-001` (255.000, status `partial`) →
   jurnal `J-DEMO-AP-001` balance. Utang sisa 300.000 tampil di KPI + aging AP.
3. **Approval pending**: PO `DEMO-PO-002` (`draft`, approval pending) → panel
   "Perlu perhatian" dashboard + badge governance + topbar.
4. **Stok menipis**: `DEMO-003` tersedia 2 → kartu KPI + daftar "Stok menipis".
5. **HR**: karyawan `DEMO-001` hadir hari ini → tile SDM dashboard.
6. **Manufaktur**: WO `DEMO-WO-001` (`completed`, 10 pcs) — BOM v1
   di-snapshot (1× `DEMO-MAT-001` @5.000) → issue aktual @6.000 →
   variance **−10.000** + 10 `DEMO-FG-001` masuk stok. Cek: Work Order →
   histori released→in_progress→completed + kartu stok material/FG.
7. **Rental**: reservasi `DEMO-RNT-001` (`reserved`, `DEMO-UNIT-001`,
   +7..+9 hari, tarif 250.000, **deposit 100.000**) → uji bentrok:
   booking tumpang tindih unit sama DITOLAK ramah.
8. **Thrift**: lot `DEMO-LOT-001` + item `DEMO-THR-001/002` (unik,
   cost_share 500.000/500.000) → live reserve 1× OK, reserve ulang
   DITOLAK (anti double-sell); kode duplikat ditolak unique index.
9. **Studio**: proyek `Katalog Demo` + deliverable `Draft 1` v1 → tampil
   di index studio.
10. **Pet**: pemilik `Pemilik Demo` + `Momo Demo` (kucing Anggora) →
    grooming `DEMO-GRM-ORD-001` (`queued`, 80.000) + boarding
    `DEMO-BRD-001` (`reserved`, deposit 50.000).
11. **Dashboard**: KPI + tren 6 bulan + per-entitas + keuangan/operasional/SDM
    terisi dari data di atas (bukan angka statis).
12. **Negatif (harus ditolak ramah)**: overpayment invoice lunas, oversell melebihi
    stok, duplikat `vendor_ref` supplier — lihat `SalesFlowTest`/`PurchasingMatchTest`.

## 5. Troubleshooting
- `demo:seed` ditolak di production → setuju: memang diblokir. Gunakan DB terisolasi.
- Approval tidak bisa diputus pemohon sendiri → by design (segregation of duties);
  gunakan akun finance untuk approve PO demo.
- Periode akuntansi: posting memakai tanggal hari ini; pastikan `fiscal_periods`
  bulan berjalan `open` (OrgSeeder membuka periode berjalan saat seed).
- Stok demo habis setelah dimainkan → jalankan ulang `demo:seed` (stok awal
  hanya ditanam sekali via `demo-opening`; transaksi `DEMO-*` dilewati bila ada).

## 6. Cakupan yang BELUM didemo-kan (jujur, bukan pura-pura)
POS shift, payroll approve — modul tersedia di menu (smoke 200) tetapi belum
punya paket data demo; payroll approve menunggu aturan resmi (butir 6
DECISIONS) sehingga tidak didemo-kan agar tidak mengarang kebijakan.
Dimainkan manual mengikuti USER_MANUAL. Tidak ada simulasi integrasi
(gateway/marketplace/WA/GPS) — menunggu provider & kredensial (DECISIONS butir 7).

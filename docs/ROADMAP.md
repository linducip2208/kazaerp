# Roadmap

- v0.2 (✅): delegasi approval UI, grafik tren, notifikasi DB, payment AP, retur pembelian stok, audit viewer read-only penuh.
- v0.3 (✅): mapping jurnal payroll, 2FA, file privat, rekam medis terbatas, alokasi lot, voucher/loyalty, rekonsiliasi bank — eliminasi intercompany otomatis & HPP FIFO/average tetap menunggu keputusan bisnis.
- v0.4 (sebagian ✅):
  - ✅ Sanctum API v1 POS (shift open/close/checkout, harga tier, guard entitas) — fondasi POS/mobile.
  - ✅ GPS transport: push posisi driver + halaman tracking + link peta OSM.
  - ✅ Harga tier per pelanggan (SO/invoice/quote/POS + API).
  - ✅ QC/karantina batch dari lot GRN + guard stok keluar.
  - ✅ MFG output/variance (biaya standar snapshot vs aktual, cap qty, FG receipt).
  - ✅ Komisi penjualan (akrual saat lunas + approve Finance).
  - ✅ Queue worker nyata: `kaza:overdue-alerts` + job (scheduler 08:00, idempoten).
  - ✅ Hardening v0.4.1: read-only auditor/viewer server-side, reset password diuji penuh, backup+restore terverifikasi (dump → scratch → identik).
  - ⏳ Sinkronisasi marketplace adapter nyata — menunggu kredensial/pilihan vendor.
  - ⏳ GPS vendor produksi (server-side provider) — arsitektur sudah siap menerima.
- v1.0: UAT + hardening produksi (2FA, header, backup teruji) + go-live bertahap per entitas.

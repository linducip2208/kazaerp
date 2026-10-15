# User Manual (ringkas)

1. Login dengan email + kata sandi (+ kode authenticator bila 2FA aktif; aktifkan via Profil → Kelola 2FA).
2. Pilih konteks Badan Usaha/Cabang di topbar (hanya yang berhak).
3. Dashboard menampilkan KPI sesuai peran. 4. Master: CRM → Pelanggan/Supplier; Master → Produk.
5. Jual: Sales → SO (isi baris, simpan) → Setujui → Invoice (harga dikosongkan = otomatis harga tier pelanggan; kode voucher opsional; otomatis posting + potong stok) → Bayar (partial boleh; status ikut total; saat lunas poin loyalty + komisi sales masuk) → cetak PDF.
6. Kasir: POS → buka shift → checkout → tutup shift (catat selisih).
7. Beli: PR → PO → terima barang (bisa partial) → invoice vendor (cek status match; HOLD bila beda) .
8. Keuangan: Jurnal (wajib balance) → Buku Besar/Neraca/Laba Rugi → tutup periode tiap bulan.
9. HR: Karyawan → Absensi → Payroll (hitung → setujui → slip PDF).
10. Approval: menu ✅ — setujui/tolak; pemohon tidak bisa setujui sendiri.
11. Laporan: filter tanggal → Export CSV. Import: Reports → Import → upload CSV → perbaiki baris error.
12. Inventory: terima barang isi kolom lot/expired → batch dibuat otomatis → Inventory → Batch & QC untuk release/karantina; batch karantina atau kedaluwarsa diblokir keluar.
13. Produksi: MFG → Work Order → Issue material (catat biaya aktual) → Produce (good/reject ≤ rencana) → variance biaya tampil di detail WO.
14. Komisi: Finance → Komisi Penjualan — akrual otomatis saat invoice lunas (rate dari config entitas); approve oleh Finance.
15. Transport: Pet & Transport → Tracking GPS (driver kirim posisi via `POST /api/v1/trips/{id}/positions`; halaman menampilkan posisi terakhir + link peta OSM).
Masalah umum: "Stok tidak mencukupi" → terima barang dulu; "Periode ditutup" → minta Finance buka kembali; "Batch tidak bisa keluar" → cek status QC/expired di Inventory → Batch & QC; 403 → minta peran/scope.

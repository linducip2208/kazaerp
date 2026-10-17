# Panduan Pengguna (per peran)

Semua peran: masuk dengan email + kata sandi (+ kode authenticator bila 2FA aktif; aktifkan via Profil → Kelola 2FA).
Setelah masuk, pilih konteks Badan Usaha/Cabang di topbar (hanya yang berhak). Dashboard menampilkan KPI sesuai peran.
Format: tanggal Indonesia (`tgl_id`), uang Rupiah (`idr`).

## Shortcut keyboard (tanpa mengubah alur)
- `/` — fokus ke kolom pencarian/filter di halaman aktif.
- `Ctrl+Enter` (atau `Cmd+Enter`) — simpan/kirim form utama (dilewati pada form berisi tombol konfirmasi destruktif).
- `Esc` — tutup drawer sidebar di layar kecil.

## Kasir (pos.operate)
POS → buka shift → checkout (harga otomatis tier pelanggan; voucher bila ada) → tutup shift (catat selisih kas).
Struk/invoice dapat dicetak (tombol Cetak; hasil cetak rapi tanpa sidebar/tombol).

## Sales (sales.*)
1. CRM → Pelanggan (cek peringatan "Kemungkinan duplikat" di detail; gabung via tombol bila berwenang `crm.convert`;
   atur opt-out promo bila pelanggan menolak).
2. Quotation → revisi bila perlu → Setujui (kedaluwarsa ditolak; versi diarsipkan otomatis).
3. Sales → SO (isi baris, simpan) → Setujui + Reservasi → Batalkan bila perlu (reservasi dilepas; SO yang sudah
   menjadi invoice tidak bisa dibatalkan).
4. Delivery Order → Setujui. Invoice → terbit + terposting + potong stok otomatis → Bayar (partial boleh;
   status mengikuti total; saat lunas: poin loyalty + komisi sales masuk) → cetak PDF.
5. Credit Note / Retur → ajukan → Setujui (otorisasi `sales.approve`).

## Pembelian (purchasing.*)
PR → PO → Setujui → terima barang/GRN (bisa partial; isi lot/expired bila batch) → invoice vendor
(cek status match; HOLD bila beda) → bayar utang (idempotensi: kirim ulang tidak menggandakan).

## Gudang/Inventory (inventory.*)
Terima barang dengan lot/expired → Inventory → Batch & QC untuk release/karantina; batch karantina atau
kedaluwarsa diblokir keluar. Stok opname menyesuaikan ledger (tercatat, bukan edit diam-diam).

## Finance (finance.*, governance.*)
1. Jurnal manual (wajib balance; idempotent) → Buku Besar / Neraca / Laba Rugi → tutup periode tiap bulan
   (periode tertutup menolak posting; reversal via menu Reverse, bukan hapus).
2. Rekonsiliasi bank → Konfirmasi. Komisi Penjualan: akrual otomatis saat invoice lunas; approve oleh Finance.
3. Expense → approve. Menu Approval: setujui/tolak (+ delegasi); pemohon tidak bisa menyetujui sendiri.
   Semua keputusan penting tercatat di audit log.

## HR (hr.*)
Karyawan → Absensi → Payroll (hitung dari komponen configurable → setujui → slip PDF; jurnal gaji terposting;
kunci dari perubahan diam-diam). Gaji bersifat rahasia (slip hanya untuk karyawan + HR berwenang).

## Produksi (mfg.*)
MFG → Work Order → Issue material (catat biaya aktual) → Produce (good/reject ≤ rencana) → variance biaya
tampil di detail WO.

## Pemilik/Eksekutif & Auditor
Dashboard KPI + laporan (filter tanggal/entitas/cabang) → Ekspor CSV (dibatasi 5000 baris; sel diawali
`=+-@` dinetralkan) / PDF / Cetak. Audit log + login activities untuk telaah. Read-only role (auditor/viewer)
otomatis ditolak bila mencoba menulis.

## Admin IT
Pengguna (buat/nonaktifkan; nonaktif langsung mencabut sesi) → Peran (matriks permission) → delegasi approval →
konteks entitas/cabang → impor CSV master (pelanggan/produk/supplier; baris error dilaporkan) →
backup (`kaza:backup` harian 02:00) → kesehatan (`kaza:health`; cocok untuk cron) →
integritas (`kaza:audit-integrity`). File sensitif hanya via controller terotorisasi (tidak ada URL publik).

## Masalah umum
"Stok tidak mencukupi" → terima barang dulu; "Periode ditutup" → minta Finance buka kembali;
"Batch tidak bisa keluar" → cek status QC/expired di Inventory → Batch & QC; 403 → minta peran/scope;
"Email atau kata sandi salah" → gunakan Lupa kata sandi; akun nonaktif → hubungi administrator;
"Pembayaran melebihi sisa utang" → cek paid_total invoice vendor; approval tidak bisa diputuskan →
cek wewenang `governance.approve` atau delegasi aktif.

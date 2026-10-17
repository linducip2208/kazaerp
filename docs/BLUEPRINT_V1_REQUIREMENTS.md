# Blueprint ERP Kazaka Group / MZA Holdings v1.0 — Transkripsi Requirement

> Sumber: `Blueprint_ERP_Kazaka_Group_MZA_Holdings.pdf`, v1.0 / 14 Oktober 2026, klasifikasi INTERNAL,
> disiapkan untuk Ghassan / Owner. Ditranskripsikan ke repo agar searchable dan tertaut ke traceability.
> Status dokumen: **usulan** — angka target, tarif, SLA, dan contoh jurnal memerlukan persetujuan pihak
> terkait sebelum dipakai. PDF adalah definisi kebutuhan, BUKAN bukti implementasi/integrasi.

## 01 Ringkasan eksekutif — prioritas & hasil minimum
- **P0 fondasi**: Organisasi, akses, data induk, audit → struktur & identitas transaksi dapat dipercaya.
- **P1 inti**: Penjualan, pembayaran, stok, pembelian, kas/bank → tidak ada transaksi penting di luar pencatatan.
- **P1 operasional**: Pet care, custom, apparel, thrift, rental → jadwal & barang tidak bentrok; pekerjaan terlacak.
- **P2 penguatan**: Akuntansi lengkap, SDM, marketing, konsolidasi → kinerja & laba per brand dapat dievaluasi.
- **P3 optimasi**: Portal, integrasi lanjutan, analitik.

## 03 Organisasi & cakupan brand
Struktur: holding > badan usaha > brand > cabang/lokasi > divisi > unit layanan.
Holding dan brand TIDAK otomatis badan usaha legal.
- Kazaka Group: Katze Nesia (kucing, grooming, klinik/ranap, cat hotel, room care, studio, transport);
  Produk Katze Nesia/Kazaka (pet care retail/reseller/dropship/grosir); ZATKE ART; ETZKA SOCIETY.
- MZA Holdings: MZA Culture; MZA Kids; ZRYN ID/ZRYN Denim; Balya Rent.
- **ZAYAN Style: PERLU KONFIRMASI** (thrift crewneck/hoodie; penempatan holding ditetapkan owner).
- Dimensi transaksi: badan usaha wajib; brand & lokasi wajib bila relevan; pusat biaya/proyek/kanal/PIC
  sesuai jenis. Brand bukan pengganti akun. Saldo dicatat pada entitas pemiliknya.
- Lokasi awal (validasi): Bekasi Timur/Duren Jaya, Graha Mutiara. Nama ZRYN ID vs ZRYN Denim belum diputus.
- Ekspansi: kode historis tidak diubah; tutup = nonaktif (bukan hapus); antar-entitas = dokumen + saldo
  timbal balik; internal satu entitas = pindah lokasi/pusat biaya.

## 04 Arsitektur proses & integrasi
- Order to cash: Lead > penawaran > order > pekerjaan/pengiriman > invoice > penerimaan.
- Procure to pay: Permintaan > PO > penerimaan > tagihan > pembayaran.
- Service delivery: Booking > alokasi > checklist > penyelesaian.
- Make to order: Brief > desain > persetujuan > produksi > QC > kirim.
- Rental cycle: Booking > fitting > keluar > kembali > inspeksi > perawatan.
- People to payroll: Karyawan > jadwal > absensi > komponen > review > payroll.
- Record to report: Subledger > rekonsiliasi > penyesuaian > tutup buku.
- Aturan: satu pelanggan banyak kontak/alamat/hewan/order/invoice/bayar; satu order banyak baris/WO/
  pengiriman/alokasi; satu pembayaran ke banyak invoice (total ≤ dana sah); rental & thrift per unit,
  produk per SKU/batch, hewan per ID; catat sumber/external ID/waktu sync/status/gagal; kunci
  idempoten; webhook terverifikasi + antrean retry; pengulangan event tidak boleh ganda;
  posting atomik; master tidak mengubah dokumen historis; snapshot harga/alamat/tarif/biaya.

## 05 Data induk & identitas
- Organisasi: kode entitas/brand/lokasi/pusat biaya/status. Pelanggan: ID/nama/telepon/alamat/sumber/
  preferensi; duplikasi = saran gabung (histori tetap). Hewan: ID unik, pemilik/status, foto, ras,
  kelamin, tgl masuk; riwayat pemilik/status tercatat. SKU: kode/kategori/varian/satuan/metode
  pelacakan/stok minimum; tanpa SKU ganda; konversi valid. Layanan: kode/durasi/tarif/kapasitas/
  bahan/petugas kompeten. Vendor: nama/kontak/rekening/termin/kategori; perubahan rekening
  diverifikasi independen. Karyawan: ID/jabatan/atasan/entitas/lokasi/kontrak/komponen; sensitif
  terpisah. Aset: kode unit/nilai/lokasi/pengguna/kondisi/servis; fisik cocok register. Akun: kode/tipe/
  mata uang/kontrol/status; akun kontrol tanpa jurnal bebas.
- Nomor dokumen (usulan, contoh saja): tipe-entitas-lokasi-periode-urutan, mis. INV-KNI-DJ-202610-00001
  (KNI wajib dipetakan ke entitas legal yang benar). ID database tetap di balik nomor. Urutan batal
  tidak dipakai ulang.
- Migrasi: template, kolom wajib, kamus nilai, pemilik, cut-off; impor via staging + baris ditolak;
  jangan auto-merge pelanggan hanya karena nomor sama (alias + jejak asal dipertahankan).

## CRM (P1) — Masuk > kualifikasi > penawaran > follow-up > menang/kalah > retensi
- CRM-01: Lead baru ditugaskan berdasar brand, jam layanan, kapasitas; pemindahan owner tercatat.
- CRM-02: Pipeline terpisah (produk, layanan, custom, grosir, rental); tiap tahap wajib definisi keluar.
- CRM-03: Jadwal follow-up + pengingat, hasil percakapan, tindakan berikut, eskalasi lead terlantar.
- CRM-04: Penawaran berversi (masa berlaku, rincian harga, syarat, persetujuan diskon).
- CRM-05: Pencarian pelanggan deteksi duplikat; penggabungan oleh petugas berwenang.
- CRM-06: Riwayat lintas brand sesuai akses; opt-out promosi diterapkan ke daftar kampanye.
- Kontrol: CS tak bisa sahkan pembayaran tanpa bukti+verifikasi; ekspor kontak dibatasi; pesan promosi
  via alur+persetujuan. Uji kunci: lead sama dari 2 kanal → saran cocok, sumber dipertahankan, tanpa
  paksa ganda.

## SAL (P1) — Penawaran > order > pemenuhan > invoice > pembayaran > selesai
- SAL-01: Order campuran barang/jasa, paket, DP, bertahap, split tender.
- SAL-02: Reservasi stok saat syarat terpenuhi; kedaluwarsa mengembalikan ketersediaan.
- SAL-03: Harga retail/reseller/grosir ikut daftar harga + periode; perubahan manual diaudit.
- SAL-04: Pembayaran pending/verified/failed/reversed; bukti unggah ≠ verifikasi otomatis.
- SAL-05: Tutup shift cocokkan kas sistem/fisik/nontunai/setoran/selisih.
- SAL-06: Cetak/kirim invoice & tanda terima; lunas hanya bila alokasi sah memenuhi tagihan.
- Kontrol: order batal tak hapus histori; posted dikoreksi via kredit/pembalikan; lebih bayar = kredit
  pelanggan (bukan omzet). Uji kunci: order 1 jt + DP 300 rb → sisa 700 rb; DP bukan pendapatan.

## RET (P1) — Laporan > verifikasi > keputusan > tindakan > penyelesaian > evaluasi
- RET-01: Tiket tertaut transaksi/barang/hewan/lokasi/petugas; pelanggan dapat nomor pelacakan.
- RET-02: Urgensi menentukan eskalasi; insiden hewan → penanggung jawab klinis.
- RET-03: Retur diterima di lokasi pemeriksaan; stok tersedia setelah keputusan QC.
- RET-04: Refund sebagian/penuh pakai referensi asli + rekening terverifikasi.
- RET-05: Keputusan = perbaikan/penggantian/kredit/refund/penolakan beralasan.
- RET-06: Voucher/poin/komisi dikoreksi dari nilai bersih pasca-batal.
- Kontrol: pemohon refund ≠ penyetuju final; refund ≤ saldo refundable; dokumen koreksi keuangan.
  Uji kunci: double refund dibatasi saldo; barang rusak tak masuk stok jual.

## CAT (P1) — Masuk > pemeriksaan > karantina > perawatan > siap jual > reservasi > serah terima
- CAT-01: Registrasi unik + foto + dokumen asal; pindah kandang tercatat waktunya.
- CAT-02: Checklist penerimaan & karantina ikut SOP + instruksi dokter.
- CAT-03: Catat berat/makan/minum/eliminasi/muntah/perilaku/tindakan/bukti observasi.
- CAT-04: Siap jual perlu checklist lengkap + clearance berwenang.
- CAT-05: Reservasi berbatas waktu + DP + larangan double-sell; batal memulihkan status.
- CAT-06: Serah terima = kondisi aktual + dokumen + edukasi + benefit + after-sales.
- Kontrol: sistem tak menetapkan diagnosis; biaya tampil manajerial; sakit bisa blokir order.
  Uji kunci: diblokir dokter tak bisa diserahterimakan; 2 CS tak bisa konfirmasi hewan sama bersamaan.

## GRM (P1) — Booking > pemeriksaan awal > persetujuan > antrean > grooming > QC > pulang
- GRM-01: Booking pesan petugas/meja/ruang/slot; durasi ikut paket + kapasitas.
- GRM-02: Form penerimaan: kulit/bulu/perilaku/barang bawaan/info pelanggan.
- GRM-03: Perlu penilaian dokter → pekerjaan ditahan sampai keputusan.
- GRM-04: Tambahan layanan/ubah harga perlu persetujuan pelanggan tersimpan.
- GRM-05: Checklist + foto sebelum/sesudah + QC wajib sebelum selesai.
- GRM-06: Pakai shampoo/parfum/bahan mengurangi stok (standar/aktual terverifikasi).
- Uji kunci: 2 layanan petugas+jam sama ditolak; biaya tanpa persetujuan tak bisa jadi tagihan final.

## VET (P1) — Registrasi > triase > konsultasi > pemeriksaan > tindakan/resep > bayar > kontrol
- VET-01: Rekam medis tertaut hewan+pemilik; tiap kunjungan ada dokter + waktu catat.
- VET-02: Pemeriksaan & diagnosis hanya disahkan peran klinis.
- VET-03: Tindakan perlu persetujuan per kategori; simpan versi formulir + penyetuju.
- VET-04: Resep = obat/aturan/satuan/jumlah/instruktur; dispensing tertaut batch stok.
- VET-05: Lampiran lab/gambar; koreksi = addendum + alasan + penulis.
- VET-06: Kontrol/rujukan = jadwal + instruksi pulang + status tindak lanjut.
- Kontrol: CS lihat data tagihan saja; tanpa dosis baku/keputusan klinis otomatis.
  Uji kunci: resep belum disahkan tak bisa didispensing; ubah diagnosis pasca-tutup = addendum.

## INP (P1) — Admission > penempatan > rencana > monitoring > evaluasi > discharge
- INP-01: Kandang berzona (sehat/karantina/infeksius/noninfeksius); kapasitas + sanitasi jelas.
- INP-02: Pindah pasien catat asal/tujuan/waktu/alasan/otorisasi.
- INP-03: Instruksi berversi; tugas aktif + prioritas per shift.
- INP-04: Obat catat terjadwal/aktual/pelaksana/dosis + kejadian tak diberikan.
- INP-05: Observasi wajib; perubahan kondisi = eskalasi parameter dokter.
- INP-06: Discharge perlu evaluasi dokter + edukasi + obat pulang + administrasi/pengecualian sah.
- Kontrol: tugas lewat waktu TIDAK boleh ditandai tepat waktu via ubah timestamp.
  Uji kunci: kandang terisi ditolak; obat terlewat tampil di serah terima; discharge tanpa clearance diblokir.

## HOT (P1) — Booking > verifikasi > check-in > perawatan > perpanjang/check-out > sanitasi
- HOT-01: Kalender kapasitas per unit + jeda sanitasi; kebijakan malam/hari ditampilkan.
- HOT-02: Check-in = kondisi awal + foto + syarat + inventaris titipan.
- HOT-03: Makan/kebiasaan/instruksi tampil di tugas per shift.
- HOT-04: Konsumsi/aktivitas/eliminasi/kondisi tercatat.
- HOT-05: Perpanjang cek booking berikut + kapasitas + persetujuan + tagihan.
- HOT-06: Check-out cocokkan barang/kondisi/bayar/penjemput/bukti.
- Kontrol: kandang tersedia kembali hanya setelah sanitasi.
  Uji kunci: perpanjang tabrakan = pindah sah atau ditolak (tak timpa booking lama).

## PRC (P1) — Permintaan > survei > penawaran > jadwal > pelaksanaan > QC > tagihan
- PRC-01: Survei simpan luas/jenis/kondisi/risiko/batas yang disepakati.
- PRC-02: Penawaran pisahkan paket/tambahan/transport/khusus.
- PRC-03: Jadwal pesan tim/alat/kendaraan.
- PRC-04: Checklist sebelum/sesudah + dokumentasi per area.
- PRC-05: Bahan/jam/perjalanan/rework membentuk biaya.
- PRC-06: Tanda terima hasil/kekurangan; jadwal berulang dapat dibuat.
- Uji kunci: tambah area tanpa persetujuan tak ubah nilai; alat diservis tak bisa dialokasi.

## STD (P1) — Booking > brief > sesi > seleksi > edit > review > delivery
- STD-01: Booking alokasi studio/fotografer/perlengkapan + durasi persiapan.
- STD-02: Brief = konsep/referensi/jumlah/kebutuhan pakai.
- STD-03: Seleksi pelanggan = daftar terkonfirmasi + batas waktu.
- STD-04: Editor terima tugas berversi + koreksi + tenggat.
- STD-05: Revisi dibatasi paket; tambahan = penawaran disetujui.
- STD-06: Delivery tautan terbatas + masa akses + bukti; cadangan ikut kebijakan.
- Kontrol: asli & final tak ditimpa tanpa versi; file A tak bisa dibuka B.
  Uji kunci: ganti final pertahankan file+persetujuan lama.

## TRN (P1) — Order > rencana rute > penugasan > pickup > perjalanan > serah terima
- TRN-01: Order simpan kontak/titik/instruksi kebutuhan hewan yang disetujui.
- TRN-02: Jadwal timbang durasi/kapasitas/jeda; rute aktual bila tersedia.
- TRN-03: Checklist kendaraan/kandang/identitas/kondisi sebelum berangkat.
- TRN-04: Status pickup/berangkat/tiba/tertunda/selesai + waktu + pelaksana.
- TRN-05: Serah terima = penerima sah + kondisi; insiden ada eskalasi.
- TRN-06: BBM/tol/parkir/vendor/km/servis untuk biaya.
- Kontrol: supir tak bisa tutup order tanpa bukti wajib/pengecualian atasan.
  Uji kunci: kendaraan+supir slot sama tak bisa ganda; bukti wajib tertaut order.

## PET (P1) — Beli/produksi > QC > stok batch > order > kirim > after-sales
- PET-01: SKU beda untuk ukuran/aroma/kemasan yang ubah stok+harga.
- PET-02: Batch = sumber/masa simpan/status QC; karantina diblokir jual.
- PET-03: Ambil ikuti kedaluwarsa terdekat (pengecualian beralasan).
- PET-04: Harga & MOQ reseller/grosir terpisah; kredit dibatasi limit+umur.
- PET-05: Dropship simpan identitas pengirim + bukti kirim.
- PET-06: Lacak batch = sisa + pakai internal + penerima (penarikan).
- Uji kunci: batch diblokir pasca-QC → order belum kirim tertahan, penerima lama tertelusur, stok tetap tercatat.

## ART (P1) — Brief > DP > desain > persetujuan > produksi > QC > pelunasan > kirim
- ART-01: Brief terstruktur + lampiran asli; tiap ubah = versi + ringkasan.
- ART-02: Syarat DP & setuju desain mengunci rilis produksi.
- ART-03: Routing desain/lukis-jahit/finishing/QC/packing per PIC.
- ART-04: Kapasitas & tenggat per tahap; massal = kirim bertahap.
- ART-05: Rework = sebab + bahan/jam + penanggung biaya.
- ART-06: QC bandingkan foto/brief vs hasil; lunas & rilis ikut kebijakan.
- Kontrol: ubah pasca-approve = change order; DP ≠ laba.
  Uji kunci: revisi ukuran pasca-produksi pertahankan brief lama + setuju biaya/jadwal + update bahan.

## APP (P1) — Desain > BOM > bahan > produksi > QC > stok/order > kirim
- APP-01: Varian per kombinasi relevan; polos vs cetak dibedakan.
- APP-02: BOM = bahan/cetak/label/kemas/toleransi; versi dikunci saat rilis.
- APP-03: Family set dipecah komponen ukuran tiap anggota (bukan stok generik).
- APP-04: File cetak depan/belakang = ukuran/posisi/persetujuan (anti tertukar).
- APP-05: Produksi = baik/reject/rework/sisa; jasa vendor = biaya.
- APP-06: Custom cek bahan & kapasitas sebelum janji tanggal.
- Uji kunci: family ayah L/ibu M/2 anak beda ukuran kurangi komponen tepat; file depan kecil tak tertukar belakang besar.

## THR (P1) — Terima lot > sortir > perawatan > kode item > live/jual > packing > kirim
- THR-01: Bal/lot = biaya perolehan + alokasi konsisten disetujui finance.
- THR-02: Item satuan berkode unik; kondisi/cacat sebelum siap jual.
- THR-03: Laundry/perbaikan/steaming tambah biaya + ubah kesiapan.
- THR-04: Sesi live = host/admin/slot/daftar item/harga sah/hasil order.
- THR-05: Reservasi berbatas bayar; kanal lain terima status terakhir.
- THR-06: Packing pindai kode; paket usaha = jumlah/kategori/aturan random disetujui.
- Kontrol: satu unit tak terjual 2×; non-realtime = alokasi kanal/buffer.
  Uji kunci: 2 checkout bersamaan 1 kode = 1 reservasi sah; retur diperiksa ulang sebelum jual lagi.

## RNT (P1) — Booking > DP > fitting > persiapan > keluar > kembali > inspeksi > laundry
- RNT-01: Kalender blokir sewa + fitting/persiapan + jeda laundry.
- RNT-02: Booking = unit/peminjam/durasi/DP/deposit/syarat.
- RNT-03: Checklist keluar/kembali cocokkan unit/aksesori/foto.
- RNT-04: Perpanjang sah hanya bila tak tabrak reservasi berikut/solusi disetujui.
- RNT-05: Rusak/telat = bukti + hitung + setuju kebijakan.
- RNT-06: Deposit direkonsiliasi ke kembali/potongan sah; unit ditahan sampai layak.
- Kontrol: deposit ≠ pendapatan; ganti unit diam-diam dilarang; potongan ditinjau (bukan otomatis).
  Uji kunci: sewa habis Minggu + laundry Senin → booking Senin ditolak bila belum siap; deposit
  sebagian kembali sisakan saldo + alasan jelas.

## INV (P1) — Terima > simpan > reservasi > ambil/pakai > mutasi > hitung > koreksi
- INV-01: on-hand/reserved/available/quarantine/damaged/in-transit terpisah.
- INV-02: Ledger tiap terima/keluar; saldo bukan angka bebas edit.
- INV-03: Mutasi 2 langkah (kirim+terima); selisih = exception.
- INV-04: Konversi satuan = faktor sah + rounding; expiry ikut terbawa.
- INV-05: Opname cut-off/kontrol berjalan; blind count kurangi bias.
- INV-06: Adjustment = alasan + bukti + ambang approve + dampak nilai tertelusur.
- Kontrol: negatif diblokir (barang ditetapkan); backdate pasca-tutup = buka resmi/koreksi berjalan;
  perusahaan vs konsinyasi dipisah. Uji kunci: kirim 10 terima 8 → 2 tetap penyelesaian selisih;
  2 petugas ambil terakhir → 1 berhasil.

## PUR (P1) — PR > persetujuan > RFQ > PO > terima > tagihan > bayar
- PUR-01: PR = tujuan/pusat biaya/urgensi/budget sebelum setuju.
- PUR-02: Perbandingan penawaran = harga/mutu/lead time/alasan.
- PUR-03: PO = nilai/spesifikasi/termin/versi; terima sebagian didukung.
- PUR-04: 3-way match PO–terima–tagih; jasa = berita acara.
- PUR-05: Lebih harga/qty di luar toleransi = antrean pengecualian.
- PUR-06: Vendor dinilai (mutu/tepat/retur/respons); ubah rekening verifikasi terpisah.
- Kontrol: darurat = jalur khusus + review; vendor baru & bayar tak disetujui pemohon tunggal;
  invoice vendor duplikat terdeteksi via kunci. Uji kunci: invoice 100 vs terima 80 = hold selisih
  (kecuali prabayar sah tercatat).

## FIN (P1) — Dokumen > verifikasi > persetujuan > pembayaran > rekonsiliasi
- FIN-01: Terima dialokasi ke invoice/DP/kredit; selisih tidak disembunyikan.
- FIN-02: Jadwal bayar tampilkan kas/jatuh tempo/prioritas/otorisasi.
- FIN-03: Rekonsiliasi bank hubungkan mutasi–ledger; unmatched ditindaklanjuti.
- FIN-04: Kas kecil = limit/pemegang/bukti/saldo/replenish.
- FIN-05: AR = aging/limit/reminder/dispute; hapus butuh otorisasi.
- FIN-06: AP = invoice + DP vendor + kredit nota + parsial.
- Kontrol: bukti transfer ≠ pengganti rekonsiliasi; maker-checker bayar; unidentified = akun
  penampung terkontrol. Uji kunci: mutasi bank ganda diimpor 2× tak ganda; parsial tetap di aging
  sebesar sisa.

## ACC (P1) — Subledger > posting > rekonsiliasi > adjustment > close > konsolidasi
- ACC-01: Posting map transaksi→akun, debit = kredit.
- ACC-02: Pendapatan/DP/deposit/stok/HPP/aset/beban dibedakan sesuai kebijakan disetujui.
- ACC-03: Rekonsiliasi akun kontrol ↔ subledger (kas/AR/AP/stok/aset/deposit).
- ACC-04: Biaya bersama dialokasi via dasar terdokumentasi + versi periode.
- ACC-05: Intercompany = pasangan entitas + saldo timbal balik + selisih diselesaikan.
- ACC-06: Tutup kunci posting; konsolidasi = mapping akun + eliminasi internal.
- Kontrol: jurnal final dibalik (bukan dihapus); buka periode = persetujuan + jejak; pajak/final
  ditinjau kompeten (blueprint tak tetapkan tarif). Uji kunci: internal tak naikkan omzet grup
  pasca-eliminasi; AR/AP cocok subledger; neraca seimbang; tutup tolak backdate.

## HRM (P1) — Kebutuhan > rekrut > onboarding > penempatan > evaluasi > offboarding
- HRM-01: Permintaan terkait struktur/jumlah/budget/alasan/persetujuan.
- HRM-02: Pipeline = screening/interview/tes/penawaran/keputusan.
- HRM-03: Onboarding = dokumen/orientasi/training/akun/aset/PIC.
- HRM-04: Penempatan & kontrak histori efektif; lintas brand tak timpa data lama.
- HRM-05: Absensi/izin/cuti/koreksi via persetujuan; jadwal berversi dipublikasikan.
- HRM-06: Offboarding cabut akses, rekonsiliasi aset, serah terima, administrasi akhir.
- Kontrol: data identitas/rekening/kesehatan tak terbuka ke semua atasan.
  Uji kunci: pindah brand = 1 ID + histori; nonaktif tak bisa login tapi histori pertahankan nama.

## PAY (P1) — Cut-off > validasi > hitung > review > approve > bayar > slip
- PAY-01: Komponen tetap/variabel = dasar + efektif + prorata ditetapkan.
- PAY-02: Lembur & koreksi masuk hitung pasca-setuju; terlambat transparan.
- PAY-03: Komisi = basis jelas (lunas/bersih, margin, retur, tunggu) sesuai kebijakan.
- PAY-04: Preview tampilkan beda bulan lalu + data hilang + nilai tak wajar.
- PAY-05: Approve kunci batch; file bayar & slip sesuai akses (tanpa buka gaji tim).
- PAY-06: KPI = formula/target/bobot/sumber/penilai/keberatan/rencana.
- Kontrol: tarif ikut konfigurasi terverifikasi; pembuat ≠ satu-satunya penyetuju; ulang dibatasi
  batch/ref unik. Uji kunci: retur pasca-komisi = adjustment periode berikut; gaji tak surut tanpa
  otorisasi + histori.

## MKT (P1) — Rencana > brief > produksi > review > publish > distribusi > evaluasi
- MKT-01: Kalender lintas brand = format/tenggat/status/reviewer/link final.
- MKT-02: Brief = tujuan/audiens/pesan/CTA/visual/versi aset.
- MKT-03: KOL = rate card/deliverables/kontrak/barang/jadwal/hak pakai/bukti unggah.
- MKT-04: Campaign = biaya media/produksi/talent/sampel/fee.
- MKT-05: Voucher/UTM/referral + atribusi terekam; 1 order tak ganda lintas sumber.
- MKT-06: Affiliate = saldo pending/eligible/paid; batal/retur koreksi komisi.
- Kontrol: metrik ber-tanggal+sumber; ROAS ≠ laba. Uji kunci: 1 order 2 campaign = 1 model atribusi
  resmi / bobot total 100%.

## LOY (P1) — Daftar > aktif > terima benefit > gunakan > evaluasi/perpanjang
- LOY-01: Program = kriteria/manfaat/biaya/validitas/brand-lokasi penerima.
- LOY-02: Voucher = kode/kuota/minimum/periode/kombinasi.
- LOY-03: Poin/kredit ≠ tunai; ledger = beri/pakai/expiry/koreksi.
- LOY-04: Reminder ikut jadwal disetujui; rekomendasi medis dari dokter.
- LOY-05: Segmentasi repeat/dormant/bernilai terukur.
- LOY-06: Lintas brand catat penanggung biaya + settlement internal.
- Kontrol: 1 voucher tak ditebus 2× bersamaan; hormati opt-out.
  Uji kunci: 2 kasir tebus sekali pakai bersamaan = 1 berhasil; retur kembalikan/batalkan benefit.

## GOV (P1) — Terbitkan standar > tugaskan > laksanakan > verifikasi > perbaiki
- GOV-01: SOP = nomor/versi/penyetuju/berlaku/bukti dibaca per jabatan.
- GOV-02: Tugas rutin otomatis = PIC/tenggat/checklist/eskalasi.
- GOV-03: Checklist kritis butuh bukti; pengecualian = alasan + atasan penyetuju.
- GOV-04: Aset = lokasi/pemegang/kondisi/servis/riwayat serah terima.
- GOV-05: Kontrak/dokumen = klasifikasi/akses/akhir/pengingat.
- GOV-06: Insiden = kronologi/dampak/akar/tindakan/PIC/tenggat/verifikasi efektivitas.
- Kontrol: SOP lama diarsip + tertelusur; aset perawatan tak tersedia booking; lampiran sensitif
  tanpa tautan publik. Uji kunci: ubah SOP tak hapus bukti versi kemarin; kritis tanpa bukti tak
  selesai kecuali pengecualian berwenang.

## 06 Hak akses — peran & batas
Owner (dashboard/laporan lingkup); Brand/store manager (no gaji/rekening tim); CS/kasir (no refund
sendiri/sahkan diagnosis); Dokter (no ubah ledger); Keeper/groomer (data dibatasi kerja);
Gudang/purchasing (pembeli ≠ satu-satunya pengesah bayar); Finance (maker/checker/approver
dibedakan); HR (lihat penghasilan dibatasi); Marketing (ekspor pelanggan perlu wewenang);
IT/auditor (bukan penyetuju bisnis). Pisahkan lihat/buat/ubah-draft/approve/posting/bayar/ekspor/
konfigurasi/batal. Lintas brand eksplisit. Lampiran ikut izin induk. Uji di API+unduhan juga.
Akun individual (larang bersama untuk approval); MFA sensitif; review mutasi/berkala; emergency =
alasan + expiry + review; nonaktif cabut sesi/token.

## 07 Persetujuan & SLA
Diskon luar aturan: CS > brand manager; Refund: CS > manager > finance; PO: divisi > purchasing >
finance; Bayar vendor: AP > pemeriksa (approver bank ≠ maker); Ubah rekening: purchasing >
verifikator (konfirmasi independen, hold); Stok: gudang > controller (bukti hitung); Payroll:
HR > finance checker; Hapus piutang/buka periode: finance > controller (owner/delegasi, alasan,
jurnal); Clearance klinis: petugas > dokter; Change order: CS/produksi > pelanggan. Mesin status:
draft > submitted > in review > approved/rejected > executed; ubah nilai/rekening/objek/lingkup
pasca-approve membatalkan persetujuan; delegasi dibatasi periode+wewenang; ditolak = revisi
berhistori. Nominal & SLA final ditetapkan manajemen (bukan kebijakan berlaku).

## 08 Posting ilustratif (BUKAN kebijakan final)
DP 300rb: Kas 300 / Uang muka pelanggan 300. Selesai 1jt: Piutang 1jt / Pendapatan 1jt.
Alokasi DP: Uang muka 300 / Piutang 300. Lunas 700: Kas 700 / Piutang 700.
Deposit terima: Kas / Kewajiban deposit; kembali penuh: sebaliknya.
Beli kredit: Persediaan / Utang vendor. Jual serah: HPP / Persediaan. Aset: Tetap / Kas-utang.
Susut: Beban susut / Akumulasi. HPP & margin ikut metode disetujui; margin dilabeli gross vs
setelah platform/komisi/overhead. Tutup: cut-off > kas > bank > AR/AP > stok/HPP > deposit/DP >
aset > payroll > intercompany > accrual > review > kunci; buka ulang = persetujuan + jejak.

## 09 KPI owner (formula + sumber; target setelah baseline)
Pendapatan bersih = diakui − koreksi/retur (ledger+invoice/kredit nota). Laba kotor = bersih − HPP
(N/A bila denominator 0). Laba operasional brand = kotor − langsung − overhead disetujui.
Kas = saldo rekonsiliasi (dana terbatas dilabeli). Lead-to-paid = unik cohort dibayar / eligible.
Repeat = ≥2 beli sah / pembeli cohort. Occupancy = unit-night pakai / tersedia (maintenance
dikecualikan). On-time = selesai ≤ komitmen (revisi diaudit). CAC/ROAS = spend/atribusi.
Aging = umur per terima/produksi + metode. Tenaga kerja = komponen periode + kewajiban.
Tampilan: grup > holding > brand > layanan/SKU > transaksi; target vs aktual; provisional dilabeli;
bedakan tgl order/pengakuan/kas; ekspor pertahankan filter + waktu ambil.

## 10 Kamus status
Order: Draft/confirmed/on hold/cancelled/closed. Payment: Pending/verified/failed/reversed
(alokasi ≤ dana sah). WO: Queued/scheduled/running/blocked/QC/completed. Reservation:
Held/confirmed/expired/released (tak tumpang tindih). Stock: Draft/posted/reversed (ledger tak
dihapus). Medical: Open/signed/closed (koreksi = addendum). Rental: Booked/out/overdue/returned/
maintenance/closed. Journal: Draft/posted/reversed (D=K). Approval: Pending/approved/rejected/
revoked. Attachment: ikut izin induk; arsip+retensi. Lintas objek: created_at/by, updated_at,
versi, organisasi, sumber, external_id, alasan ubah; waktu konsisten tampil WIB; desimal + mata
uang eksplisit; telepon dinormalisasi. Validasi: invoice tak posting lintas entitas tanpa proses sah;
reservasi dicek saat simpan; ubah qty update reservasi terkontrol; lampiran divalidasi sebelum
transisi.

## 11 Formulir standar (isi wajib + pengesahan)
Terima hewan (petugas+pelanggan; dokter bila perlu); observasi/shift (keluar/masuk; eskalasi dokter);
WO lapangan (pelaksana+pemeriksa/pelanggan); brief custom (pelanggan+produksi); QC
(QC berwenang); serah terima rental (petugas+penyewa); PR (pemohon+pemilik budget); terima barang
(penerima+QC); expense/reimburse (atasan+finance); opname (penghitung+pemeriksa+approver).
Bukti = identitas+waktu+versi+metode+bukti relevan (tanda tangan saja tak cukup); form digital
terbaca ulang sama.

## 12 NFR (target awal negosiasi, BUKAN janji layanan)
HTTPS; hash tepat; MFA sensitif; izin server; secrets terkelola. Audit: pelaku/waktu/objek/aksi/
sebelum-sesudah/alasan; batasi ubah log; samarkan sensitif. Backup terenkripsi terpisah;
RPO ≤24 jam, RTO ≤8 jam; uji pra-go-live + berkala. p95 layar umum ≤3 dtk (beban disepakati);
ekspor besar background. Kapasitas/load test berbaseline. Health check/alert/runbook; uptime
kontrak. Privasi: klasifikasi/minimisasi/retensi. Integrasi: API resmi, scope minimum, sandbox,
webhook verified, idempotency, retry, rekonsiliasi. Offline: tak diasumsikan penuh; nomor darurat
+ input ulang terkontrol. Portabilitas: ekspor master/transaksi/lampiran/kamus + docs pemulihan.
Prioritas integrasi: bank/gateway, kanal jual, WA (bila akses), absensi, file aset. Gangguan:
gateway sukses + webhook gagal = rekonsiliasi tanpa ganda; marketplace lambat = risiko stok +
alokasi/buffer; ERP down = prosedur darurat manajemen.

## 13 Implementasi bertahap (gelombang + gerbang)
0 discovery (peta, gap, pemilik, katalog, keputusan); 1 fondasi (org, akun, akses, master, nomor;
impor bersih; saldo disetujui); 2 pilot inti (1 lokasi/alur; bug kritis nol); 3 operasional
(katze/custom/apparel/thrift/rental sesuai kesiapan); 4 penguatan (payroll parallel, marketing,
alokasi, konsolidasi, cocok sumber); 5 stabilisasi (dampingan, exception, docs, support, backlog).
Migrasi 8 langkah; saldo pembuka tak gandakan histori; cutover + rollback terencana; latih per peran.

## 14 UAT kunci (UAT-01..12)
UAT-01 DP/pelunasan/refund sebagian (saldo tepat, tanpa lebih). UAT-02 dua pengguna jual terakhir
(1 sukses + konflik jelas). UAT-03 booking hotel/rental tumpuk (kapasitas + jeda dihormati).
UAT-04 obat expired/pasien belum clearance (tertahan sesuai peran). UAT-05 custom ubah pasca-approve
(versi lama ada; biaya/jadwal setuju ulang). UAT-06 kirim 10 terima 8 + opname selisih (2 terlacak;
koreksi disetujui). UAT-07 webhook/impor bank diulang (tak ganda). UAT-08 marketplace fee + retur
pasca-payout (rekonsiliasi). UAT-09 payroll/prorata/komisi/retur (aturan disetujui; parallel cocok;
slip terbatas). UAT-10 intercompany + overhead (pasangan cocok; eliminasi tak naikkan omzet).
UAT-11 akses ilegal + nonaktif (ditolak UI/API/file/ekspor). UAT-12 restore backup + cutover
(data pulih dalam target; rekonsiliasi lulus). Selesai = tanpa defect kritis + P1 lulus + tanda tangan
pemilik proses + selisih dijelaskan. Go-live = owner + finance + manajer unit + HR + IT setuju.

## 15 Keputusan awal (wewenang: sponsor/owner, project owner, finance, process owner, HR/marketing, vendor, QA)
Yang harus diselesaikan: daftar badan usaha/rekening/kepemilikan; status ZAYAN + ZRYN ID/Denim;
lokasi/kapasitas/pengguna/perangkat; HPP/pengakuan/deposit/overhead/intercompany; diskon/refund/
kredit/payroll/komisi/persetujuan; platform/anggaran/hosting/integrasi/kepemilikan kode-data;
hukum/klinis/pajak.

## 16 Checklist vendor
Multi-entitas (buku terpisah + konsolidasi); operasional spesifik (klinik/ranap, custom, thrift unik,
rental); kontrol (maker-checker, izin, audit, reversal, period lock); integrasi (API, biaya, batas,
retry, idempotency, fallback); migrasi (template, mapping, error log, rekonsiliasi, cutover);
keamanan/pemulihan (akses, backup, restore test, IR); UX (mobile, cari, barcode, antrean, cepat);
kepemilikan/exit (data, ekspor, source, docs); layanan/biaya.

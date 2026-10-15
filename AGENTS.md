# AGENT RULES — KAZAKA GROUP / MZA HOLDINGS ERP

- Gunakan Laravel, PHP, MySQL, Blade, dan Tabler sebagai stack utama.
- Jangan mengganti database atau menambahkan framework frontend utama tanpa keputusan eksplisit.
- Jangan memuat aset UI dari CDN (Tabler/Bootstrap via `npm run build` → `public/build`).
- Gunakan timezone Asia/Jakarta dan mata uang IDR. DB menyimpan UTC; tampilkan Asia/Jakarta via `tgl_id()`/`idr()`.
- Jangan menghapus file, data, migration, atau pekerjaan pengguna tanpa pemeriksaan dan perlindungan.
- Semua transaksi stok dan keuangan harus memiliki histori (ledger/jurnal) serta aturan integritas (locking, balance, idempotency).
- Semua query sensitif wajib memeriksa authorization (`perm` middleware + `hasPermission`) dan konteks legal entity (baseQuery scope).
- Jangan membuat menu atau tombol palsu: setiap route harus punya controller + view + operasi DB nyata.
- Jangan mengklaim fitur selesai tanpa implementasi dan bukti pengujian (`docs/TEST_RESULTS.md` dari run nyata).
- Perbarui dokumentasi Markdown setiap kali ada perubahan arsitektur, database, workflow, permission, atau fitur.
- Catat asumsi dan keputusan bisnis yang belum disetujui sebagai `PENDING_BUSINESS_DECISION` di `docs/DECISIONS_AND_OPEN_QUESTIONS.md`.
- Jalankan test relevan setelah perubahan dan perbaiki regresi (`php artisan test` memakai MySQL `kazaerp_testing`).
- Jangan mengekspos secret, password, data pribadi, atau file sensitif (audit log tidak menyimpan secret).
- Jangan mengubah dokumen posted dengan menghapus histori; gunakan reversal atau mekanisme koreksi yang terotorisasi.
- Utamakan implementasi end-to-end yang dapat dijalankan daripada banyak halaman kosong.

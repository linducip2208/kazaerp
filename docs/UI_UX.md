# UI/UX (Tabler lokal)

- Aset: `@tabler/core@1.6.1` + `bootstrap@5.3.8` via npm, bundel Vite → `public/build/assets/*` (CSS 835KB, JS 268KB). TIDAK ada CDN (layout hanya `@vite` + manifest lokal).
- Layout `layouts/tabler.blade.php`: topbar (brand, search, 🔔 approval badge, user menu), sidebar/navbar dropdown PER PERMISSION (tidak semua menu tampil), context switcher entity/cabang (terotorisasi), breadcrumb per halaman, toast/alert, empty state, pagination, modal konfirmasi, responsive (collapse ≤768px), print CSS (`.no-print`).
- Komponen Blade: `crud-index` (search/filter/paginate/badges), `crud-form` (text/number/date/email/password/textarea/select/select_model), `crud-show`, `doc-lines-form` (tambah/hapus baris + harga otomatis).
- Bahasa Indonesia, format `idr()`/`tgl_id()`, timezone tampil Asia/Jakarta.
- Halaman: login, profil, dashboard (4 KPI + pendapatan/entitas + SO terbaru), 40+ listing/detail/create/edit, approval, laporan, import, 403/404/500.
- Aksesibilitas: label form, kontras Tabler default; audit lanjutan pending.

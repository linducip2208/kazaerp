# POS Kaza — Aplikasi Kasir Mobile (Offline-First)

Aplikasi Flutter untuk kasir POS kazaerp. Dibuat **offline-first**: transaksi disimpan
ke antrean lokal lebih dulu, lalu disinkronkan ke server API `/api/v1` dengan
`client_ref` unik sehingga retry tidak pernah menggandakan penjualan (server idempoten).

## Fitur

- Login token Sanctum (`POST /api/v1/login`), alamat server dapat diisi & tersimpan.
- Katalog produk + harga tier pelanggan (cache lokal → tetap terlihat saat offline).
- Buka/tutup shift kasir (`opening_cash`, `closing_cash`, ringkasan selisih).
- Keranjang → bayar (tunai/transfer/QRIS) → antrean lokal → kirim ke server.
- Halaman **Antrean Sinkron**: status per transaksi (menunggu / tersinkron / ditolak),
  tombol sinkron manual, kirim ulang transaksi yang ditolak, auto-sync saat app dibuka.
- Transaksi ditolak server (4xx, mis. stok tidak cukup) disimpan dengan pesan error —
  tidak hilang, tidak diretry otomatis.

## Struktur

| File | Isi |
| --- | --- |
| `lib/models.dart` | Product, Customer, Cart, PendingSale, Shift, SaleResult |
| `lib/local_store.dart` | Persistensi SharedPreferences (token, base URL, cache, antrean) |
| `lib/api_client.dart` | Klien HTTP `/api/v1` (login, produk, pelanggan, shift, checkout) |
| `lib/pos_repository.dart` | Antrean offline + sinkron idempoten (client_ref) |
| `lib/screens/` | Login, POS (shift + katalog + keranjang), Antrean Sinkron |

## Menjalankan

```bash
flutter pub get

# Android emulator → server dev di mesin yang sama (10.0.2.2 = loopback host)
flutter run --dart-define=API_BASE=http://10.0.2.2:8099/api/v1

# Perangkat fisik → IP LAN server, mis. http://192.168.1.10:8099/api/v1
flutter run --dart-define=API_BASE=http://192.168.1.10:8099/api/v1
```

Base URL juga bisa diketik langsung di layar login (tersimpan setelah sukses login).

## Build rilis

```bash
flutter build apk --release --dart-define=API_BASE=https://erp.example.com/api/v1
# Web (untuk uji cepat): flutter build web --release --dart-define=API_BASE=...
```

Gunakan HTTPS di produksi (token bearer dikirim di header).

Catatan build:

- **Terverifikasi 16 Okt 2026**: APK rilis terbangun → `build/app/outputs/flutter-apk/app-release.apk`
  (47,7 MB, SHA256 `2FC0CA8D…FB483D3`, log `storage/logs/flutter_build_apk2.log`).
  Sekali per mesin build: `flutter doctor --android-licenses` (butuh diterima).
- APK rilis memakai **debug signing** (default template, lihat `android/app/build.gradle.kts`) —
  untuk distribusi produksi siapkan keystore rilis + `signingConfigs` sendiri.
- Windows beda drive (pub cache `C:`, project `D:`): `kotlin.incremental=false` sudah diset di
  `android/gradle.properties` agar tidak muncul error cache Kotlin "different roots".

## Uji

```bash
flutter analyze     # harus "No issues found!"
flutter test        # unit + widget test (mock HTTP, SharedPreferences mock)
```

## Catatan integrasi server

- `client_ref` dibuat sekali per penjualan (`pos-<epoch>-<hex>`), dipakai semua retry;
  server menyimpan unique `(legal_entity_id, client_ref)` dan membalas 200 `replayed:true`
  bila retry diterima setelah penjualan pertama tersimpan (aman walau shift sudah ditutup).
- Checkout server memvalidasi shift terbuka untuk penjualan **baru**; replay tidak butuh shift.
- Produk/harga selalu dari server (cache hanya untuk tampilan offline).

# API v1 (Sanctum token)

Base: `/api/v1`. Auth: Bearer token dari `POST /api/v1/login` (throttle 10/menit). Token abilities: `api.access`.
Logout `POST /api/v1/logout` mencabut token saat ini.

| Method | Endpoint | Permission | Deskripsi |
|---|---|---|---|
| POST | `/api/v1/login` | — (akun aktif) | `{email,password}` → `{token, user}` |
| GET | `/api/v1/me` | auth | Profil + roles + konteks |
| POST | `/api/v1/logout` | auth | Cabut token |
| GET | `/api/v1/invoices?from=&to=&legal_entity_id=` | sales.view | Daftar invoice (maks 100, guard entitas) |
| GET | `/api/v1/stock` | inventory.view | Saldo stok (maks 200, guard entitas) |
| GET | `/api/v1/products?q=&customer_id=` | crm.view | Produk aktif (maks 100, guard entitas) + `price`/`tier` sesuai tier pelanggan bila `customer_id` diisi |
| GET | `/api/v1/products/{id}/price?customer_id=` | crm.view | Harga 1 produk untuk pelanggan: `{price, tier, source, base_price}` |
| GET | `/api/v1/customers?q=` | crm.view | Pelanggan aktif (maks 100, guard entitas) |
| GET | `/api/v1/pos/shift` | pos.operate | Shift kasir aktif milik user |
| POST | `/api/v1/pos/shift/open` | pos.shift | `{opening_cash}` → buka shift (201; 422 bila masih ada shift terbuka) |
| POST | `/api/v1/pos/shift/close` | pos.shift | `{closing_cash}` → tutup shift + ringkasan selisih kas |
| POST | `/api/v1/pos/checkout` | pos.operate | `{lines:[{product_id,qty,price?}],pay_method(cash/transfer/qris),customer_id?}` → penjualan POS (201). `price` kosong ⇒ otomatis harga tier pelanggan; stok dipotong + jurnal posting |
| POST | `/api/v1/trips/{tripId}/positions` | pet.transport | Push GPS driver `{lat,lng,speed_kmh?}` (201; guard entitas trip) |
| GET | `/api/v1/trips/{tripId}/positions` | pet.transport | Riwayat posisi trip (maks 200, guard entitas) |

Format error: JSON `{message}` + 401/403/422. Versioning: prefix `/v1`.
Catatan: API adalah pendamping (integrasi/POS eksternal/mobile); arsitektur utama tetap Blade server-rendered.
Guard entitas: semua endpoint membandingkan `legal_entity_id` resource dengan user; `super_admin/holding_owner/executive/auditor` boleh lintas entitas.
Harga tier: resolusi retail → wholesale → reseller fallback (lihat `PriceService`); tier pelanggan menentukan harga bila `price` tidak dikirim (POS/checkout) atau dikosongkan (form web).
Bukti: `tests/Feature/Phase2Test.php::test_api_token_scope`, `tests/Feature/Phase4Test.php` (tier pricing, POS shift/checkout, GPS positions + 403 lintas entitas).

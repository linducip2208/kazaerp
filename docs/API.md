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
| GET | `/api/v1/products?q=` | crm.view | Produk aktif (maks 100, guard entitas) |

Format error: JSON `{message}` + 401/403/422. Versioning: prefix `/v1`.
Catatan: API adalah pendamping (integrasi/POS eksternal); arsitektur utama tetap Blade server-rendered.
Bukti: `tests/Feature/Phase2Test.php::test_api_token_scope` (login, scope guard 403 lintas entitas, revoke, nonaktif 401).

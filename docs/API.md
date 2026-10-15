# API

Tidak ada REST API publik pada tahap ini (aplikasi server-rendered Blade adalah arsitektur utama — disengaja).
Endpoint mesin: `GET /up` (health), `GET /health` (auth). Jika API diperlukan (aplikasi mobile/POS eksternal),
rencana: Sanctum + versioning `/api/v1`, validasi Form Request, format error `{message, errors}`, idempotency key
untuk posting. Status: PENDING (lihat ROADMAP).

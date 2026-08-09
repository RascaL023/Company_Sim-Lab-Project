# API Testing — kulala.nvim

File `.http` di folder ini mengikuti kontrak `API_REFERENCE.md` dan route di `routes/api.php`.

## Setup

1. Jalankan server: `php artisan serve` (atau `composer run dev`)
2. Pastikan DB sudah di-seed: `php artisan migrate:fresh --seed`
3. Di nvim + kulala, pilih environment **`dev`** (`http-client.env.json`)
4. Buka `auth.http` → jalankan **Login as admin** (menyimpan token ke global `token`)
5. Buka file domain lain dan jalankan request (header `Authorization: Bearer {{token}}`)

### Kredensial seed

| Role  | Email               | Password   |
|-------|---------------------|------------|
| admin | admin@wiralab.com   | password   |
| staf  | budi@wiralab.com    | password   |

## File

| File | Isi |
|------|-----|
| `auth.http` | login admin/staf, `/user`, logout (+ set global token) |
| `users.http` | CRUD users (admin only) |
| `categories.http` | CRUD categories |
| `items.http` | CRUD items + nested (units, movements, usages, …) |
| `item-units.http` | list/show/update/delete units |
| `borrowing-requests.http` | request + approve/reject/cancel + checkout/return |
| `stock-movements.http` | ledger create/update notes; quantity immutable; delete 405 |
| `usages.http` | create + verify/reject (PATCH) |
| `calibration-maintenance.http` | calibrations & maintenances |
| `audit-trails.http` | index/recent/show + filter |
| `attachments.http` | list/show/download/upload/delete |

## Tips kulala

- Variabel `@requestId`, `@itemId`, dll. di atas file — ubah sesuai data seed/response.
- Setelah login, token ada di `client.global` (`{{token}}`). Kalau hilang, login ulang.
- Response sukses resource: `{ "data": ... }`; login: `{ "token", "token_type", "user" }`.
- Jangan kirim `status` di `PATCH /borrowing-requests/{id}` — pakai approve/reject/cancel.

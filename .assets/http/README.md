# API Testing — kulala.nvim

File `.http` di folder ini mengikuti kontrak `API_REFERENCE.md` dan route di `routes/api.php`.

## Setup

1. Jalankan server: `php artisan serve` (atau `composer run dev`)
2. Pastikan DB sudah di-seed: `php artisan migrate:fresh --seed`
3. Di nvim + kulala, pilih environment **`dev`** (`http-client.env.json`)
4. Buka `auth.http` → login sesuai role yang dibutuhkan (token ke global `token`)
5. Buka file domain lain dan jalankan request (`Authorization: Bearer {{token}}`)

### Kredensial seed

| Role | Email | Password |
|------|-------|----------|
| `admin_sistem` | `admin.sistem@wiralab.com` | `password` |
| `laboran` | `laboran@wiralab.com` | `password` |
| `kepala_lab` | `kepala.lab@wiralab.com` | `password` |
| `peminjam` | `peminjam@wiralab.com` | `password` |

## File

| File | Isi |
|------|-----|
| `auth.http` | login 4 role, `/user`, logout (+ set global token) |
| `users.http` | CRUD users (`admin_sistem`) |
| `categories.http` | CRUD categories |
| `locations.http` | CRUD lokasi rak |
| `items.http` | CRUD items + nested |
| `item-units.http` | list/show/update (`location_id`) / delete |
| `borrowing-requests.http` | request + approve/reject/cancel + checkout/return |
| `stock-movements.http` | ledger; quantity immutable; delete 405 |
| `stock-opname.http` | `POST /stock-opname` (laboran) |
| `usages.http` | create + verify/reject |
| `asset-disposals.http` | propose / approve / reject |
| `notifications.http` | list, unread-count, mark read |
| `reports.http` | inventory / borrowings / damaged-assets (pdf\|excel) |
| `calibration-maintenance.http` | calibrations & maintenances |
| `audit-trails.http` | index/recent/show |
| `attachments.http` | list/show/download/upload/delete |

## Tips kulala

- Variabel `@requestId`, `@itemId`, dll. di atas file — ubah sesuai data seed/response.
- Setelah login, token ada di `client.global` (`{{token}}`). Kalau hilang, login ulang.
- Response sukses resource: `{ "data": ... }`; login: `{ "token", "token_type", "user" }`.
- Laporan & attachment download = binary, bukan JSON.
- Jangan kirim `status` di `PATCH /borrowing-requests/{id}` — pakai approve/reject/cancel.
- Notifikasi: field isi = `payload` (bukan `data`) di dalam envelope `{ "data": { ... } }`.

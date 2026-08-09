# SIMLab API Reference

Kontrak resmi untuk frontend. Base URL: `/api`. Semua path di bawah relatif terhadap base itu.

## Konvensi response

**Opsi A (wajib):** endpoint CRUD (`index` / `show` / `store` / `update`) memakai Laravel API Resource.

| Jenis | Bentuk |
|-------|--------|
| Single resource | `{ "data": { ... } }` |
| Collection (paginated) | `{ "data": [ ... ], "links": { ... }, "meta": { ... } }` |
| Collection (non-paginated) | `{ "data": [ ... ] }` |
| No content | HTTP `204` tanpa body |
| Error validasi | HTTP `422` + `{ "message": "...", "errors": { ... } }` |
| Error auth | HTTP `401` / `403` + `{ "message": "..." }` |

**Pengecualian yang disengaja:**

| Endpoint | Bentuk | Alasan |
|----------|--------|--------|
| `POST /login` | `{ "token", "token_type", "user": { ... } }` | Envelope auth (token + profil); `user` = atribut UserResource (tanpa wrap `data` ganda) |
| `POST /logout` | `204` | Tidak ada payload |
| `GET .../attachments/{id}/download` | binary file | Bukan JSON |
| `GET /reports/inventory?format=pdf\|excel` | binary PDF / Excel | Unduhan laporan, bukan JSON Resource |
| `GET /reports/borrowings?format=pdf\|excel` | binary PDF / Excel | Unduhan laporan, bukan JSON Resource |
| `GET /reports/damaged-assets?format=pdf\|excel` | binary PDF / Excel | Unduhan laporan, bukan JSON Resource |
| `DELETE` / soft-cancel sukses | `204` | Tidak ada payload |
| Guard / immutable ledger errors | `{ "message": "..." }` + `422`/`405` | Bukan resource entity |

Auth header untuk endpoint terproteksi:

```http
Authorization: Bearer {token}
```

Role: `admin_sistem` | `laboran` | `kepala_lab` | `peminjam`. Kolom status domain memakai bahasa Indonesia (lihat `SCHEMA_CHANGES.md`).

---

## Auth

### `POST /login`

- Auth: tidak
- Throttle: `5` request / menit
- Body:

```json
{
  "email": "admin@example.com",
  "password": "password",
  "device_name": "web" 
}
```

- Response `200`:

```json
{
  "token": "1|...",
  "token_type": "Bearer",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin",
    "phone": null,
    "is_active": true,
    "is_admin": true,
    "is_staff": false,
    "email_verified_at": "...",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

- `422` kredensial salah; `403` akun `is_active=false`; `429` rate limit.

### `POST /logout`

- Auth: Bearer
- Response: `204`

### `GET /user`

- Auth: Bearer
- Response `200`: `{ "data": { ...UserResource } }`

---

## Users (admin only)

Policy: semua aksi `UserPolicy` → hanya `admin`.

| Method | Path | Role |
|--------|------|------|
| `GET` | `/users` | admin |
| `POST` | `/users` | admin |
| `GET` | `/users/{id}` | admin |
| `PATCH`/`PUT` | `/users/{id}` | admin |
| `DELETE` | `/users/{id}` | admin (bukan diri sendiri) |

### `GET /users`

Query opsional: `role`, `is_active`, `search`, `per_page`.

Response paginated `UserResource`.

### `POST /users`

```json
{
  "name": "Staf Baru",
  "email": "staf@example.com",
  "password": "rahasia123",
  "role": "staf",
  "phone": "08123456789",
  "is_active": true
}
```

- `role` wajib `admin`|`staf`; email unik; password min 8.
- Response `201`: `{ "data": { ...UserResource } }`

### `PATCH /users/{id}`

Boleh mengubah `name`, `email`, `password`, `role`, `phone`, `is_active` (nonaktifkan akun).

Response `200`: `{ "data": { ...UserResource } }`

### `DELETE /users/{id}`

Response `204`. Tidak boleh menghapus akun sendiri.

---

## Categories

Auth: Bearer. (Belum ada policy khusus — semua user terautentikasi.)

| Method | Path |
|--------|------|
| `GET` | `/categories` |
| `POST` | `/categories` |
| `GET` | `/categories/{id}` |
| `PATCH`/`PUT` | `/categories/{id}` |
| `DELETE` | `/categories/{id}` |

Query `index`: `type` (`alat`|`bahan`), `per_page`.

`POST` body: `{ "name", "type": "alat"|"bahan", "description?" }` → `201` + `CategoryResource`.

---

## Items

Auth: Bearer.

| Method | Path | Keterangan |
|--------|------|------------|
| `GET` | `/items` | Filter: `type`, `condition_status`, `low_stock`, `needs_calibration`, `expired`, `search`, `sort_by`, `sort_order`, `per_page` |
| `POST` | `/items` | Lihat `StoreItemRequest` |
| `GET` | `/items/{id}` | |
| `PATCH`/`PUT` | `/items/{id}` | |
| `DELETE` | `/items/{id}` | `204` |
| `GET` | `/items/{id}/stock-movements` | nested, paginated |
| `GET` | `/items/{id}/usages` | nested, paginated |
| `GET` | `/items/{id}/calibrations` | nested, paginated |
| `GET` | `/items/{id}/maintenances` | nested, paginated |
| `GET` | `/items/{id}/audit-trails` | nested, paginated |
| `GET` | `/items/{id}/units` | nested, paginated |

Single: `ItemResource` di `data`. Index: `ItemCollection` (paginated).

---

## Item units

| Method | Path |
|--------|------|
| `GET` | `/item-units` |
| `GET` | `/item-units/{id}` |
| `PATCH`/`PUT` | `/item-units/{id}` |
| `DELETE` | `/item-units/{id}` |

Query `index`: `condition`, `needs_calibration`, `per_page`. Resource: `ItemUnitResource`.

---

## Borrowing requests

| Method | Path | Role / catatan |
|--------|------|----------------|
| `GET` | `/borrowing-requests` | staf: hanya milik sendiri; admin: semua |
| `POST` | `/borrowing-requests` | staf wajib `requested_by` = diri sendiri |
| `GET` | `/borrowing-requests/{id}` | pemilik atau admin |
| `PATCH`/`PUT` | `/borrowing-requests/{id}` | **tidak boleh** kirim `status` |
| `DELETE` | `/borrowing-requests/{id}` | soft-cancel → status `batal`, response `204` |
| `PATCH` | `/borrowing-requests/{id}/approve` | **admin**; dari `diajukan` → `disetujui` |
| `PATCH` | `/borrowing-requests/{id}/reject` | **admin**; body `{ "rejection_reason" }`; dari `diajukan` → `ditolak` |
| `PATCH` | `/borrowing-requests/{id}/cancel` | admin atau pemilik; → `batal` |

`POST` body:

```json
{
  "requested_by": 2,
  "purpose": "Praktikum",
  "items": [
    { "item_id": 1, "quantity": 1 }
  ]
}
```

Response `201`: `{ "data": { ...BorrowingRequestResource } }`.

Transisi status valid: `diajukan`→`disetujui|ditolak|batal`; `disetujui`→`diproses|batal`; `diproses`→`selesai`. Invalid → `422`.

---

## Borrowing items (checkout / return)

| Method | Path | Role |
|--------|------|------|
| `PATCH` | `/borrowing-items/{id}/checkout` | **admin**; parent harus `disetujui` |
| `PATCH` | `/borrowing-items/{id}/return` | **admin**; item harus sudah checkout, belum return |

### Checkout body

```json
{
  "expected_return_date": "2026-08-20"
}
```

(`expected_return_date` wajib, tanggal masa depan.)

Setelah **semua** item di request yang sama checkout → parent status `diproses`.

### Return body

```json
{
  "condition_after": "baik",
  "is_damaged": false,
  "check_notes": "OK",
  "damage_notes": null
}
```

- `condition_after`: `baik`|`rusak_ringan`|`rusak_berat`|`hilang`
- `damage_notes` wajib jika `is_damaged=true`
- Return rusak + punya `item_unit_id` → otomatis buat `item_maintenances`
- Setelah **semua** item return → parent status `selesai`

Response: `{ "data": { ...BorrowingItemResource } }`

---

## Stock movements

Ledger append-only.

| Method | Path | Catatan |
|--------|------|---------|
| `GET` | `/stock-movements` | filter `type`, `item_id`, `per_page` |
| `POST` | `/stock-movements` | membuat movement; stock item di-update observer |
| `GET` | `/stock-movements/{id}` | |
| `PATCH`/`PUT` | `/stock-movements/{id}` | hanya `notes` / `occurred_at`; ubah quantity → `422` |
| `DELETE` | `/stock-movements/{id}` | selalu `405` |

`POST` body:

```json
{
  "item_id": 1,
  "item_unit_id": null,
  "type": "in_purchase",
  "quantity": 10,
  "notes": "Pembelian"
}
```

`type`: `in_purchase`, `in_return`, `in_adjustment`, `out_borrow`, `out_usage`, `out_disposal`, `out_adjustment`, `transfer_in`, `transfer_out`.

---

## Usages

| Method | Path | Role |
|--------|------|------|
| `GET` | `/usages` | staf: milik sendiri |
| `POST` | `/usages` | membuat usage + `StockMovement` `out_usage` |
| `GET` | `/usages/{id}` | |
| `PATCH` | `/usages/{id}/verify` | **admin** |
| `PATCH` | `/usages/{id}/reject` | **admin**; body `{ "rejection_reason" }` |

`POST` body:

```json
{
  "item_id": 1,
  "item_unit_id": null,
  "quantity_used": 5,
  "purpose": "Praktikum"
}
```

Status usage: `dicatat` → `diverifikasi` | `ditolak`.

---

## Calibrations

| Method | Path |
|--------|------|
| `GET` | `/calibrations` |
| `POST` | `/calibrations` |
| `GET` | `/calibrations/{id}` |
| `PATCH`/`PUT` | `/calibrations/{id}` |

`POST` body: `item_unit_id`, `calibration_date`, `next_calibration_date`, `calibrated_by`, `certificate_number?`, `result` (`lulus`|`tidak_lulus`), `notes?`.

---

## Maintenances

| Method | Path |
|--------|------|
| `GET` | `/maintenances` |
| `POST` | `/maintenances` |
| `GET` | `/maintenances/{id}` |
| `PATCH`/`PUT` | `/maintenances/{id}` |

`POST` body: `item_unit_id`, `maintenance_date`, `description`, `performed_by`, `cost?`, `status?` (`selesai`|`proses`|`tertunda`), `notes?`.

---

## Audit trails

Read-only.

| Method | Path |
|--------|------|
| `GET` | `/audit-trails` |
| `GET` | `/audit-trails/recent` |
| `GET` | `/audit-trails/{id}` |

Query `index`: `action`, `auditable_type`, `auditable_id`, `user_id`, `per_page`.  
Query `recent`: `limit` (default 20).

Resource: `AuditTrailResource` (`old_values` / `new_values` = changed fields saja untuk update).

> Route `GET /audit-trails/recent` didaftarkan sebelum `apiResource` show agar tidak tertangkap sebagai `{id}`.

---

## Attachments

| Method | Path | Response |
|--------|------|----------|
| `GET` | `/attachments` | paginated `AttachmentResource` |
| `POST` | `/attachments` | multipart `file` + `attachable_type`, `attachable_id`, `type?`, `description?` → `201` |
| `GET` | `/attachments/{id}` | `AttachmentResource` |
| `DELETE` | `/attachments/{id}` | `204` |
| `GET` | `/attachments/{id}/download` | binary file |

---

## Reports

Unduhan binary (PDF via DomPDF, Excel via Maatwebsite). Parameter wajib: `format=pdf|excel`.

Akses: `kepala_lab`, `laboran`, `admin_sistem`. `peminjam` → `403`.

| Method | Path | Query | Response |
|--------|------|-------|----------|
| `GET` | `/reports/inventory` | `format=pdf\|excel` | file `laporan-stok-inventaris.pdf/.xlsx` |
| `GET` | `/reports/borrowings` | `format=pdf\|excel`, `from?`, `to?` (ISO date; filter `requested_at`) | file `laporan-riwayat-peminjaman.pdf/.xlsx` |
| `GET` | `/reports/damaged-assets` | `format=pdf\|excel` | file `laporan-aset-rusak-hilang.pdf/.xlsx` |

Contoh:

```http
GET /api/reports/inventory?format=pdf
Authorization: Bearer {token}
```

```http
GET /api/reports/borrowings?format=excel&from=2026-01-01&to=2026-01-31
Authorization: Bearer {token}
```

Content-Type tipikal: `application/pdf` atau `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.

---

## Contoh resource inti

### UserResource

```json
{
  "id": 1,
  "name": "Admin Lab",
  "email": "admin@example.com",
  "role": "admin",
  "phone": null,
  "is_active": true,
  "is_admin": true,
  "is_staff": false,
  "email_verified_at": "2026-08-09T00:00:00.000000Z",
  "created_at": "...",
  "updated_at": "..."
}
```

### Paginated envelope

```json
{
  "data": [ { "...": "..." } ],
  "links": {
    "first": "http://localhost/api/users?page=1",
    "last": "http://localhost/api/users?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "http://localhost/api/users",
    "per_page": 15,
    "to": 1,
    "total": 1
  }
}
```

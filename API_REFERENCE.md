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
| `GET /reports/inventory?format=pdf\|excel` | binary PDF / Excel | Unduhan laporan |
| `GET /reports/borrowings?format=pdf\|excel` | binary PDF / Excel | Unduhan laporan |
| `GET /reports/damaged-assets?format=pdf\|excel` | binary PDF / Excel | Unduhan laporan |
| `DELETE` / soft-cancel sukses | `204` | Tidak ada payload |
| Guard / immutable ledger errors | `{ "message": "..." }` + `422`/`405` | Bukan resource entity |

Auth header untuk endpoint terproteksi:

```http
Authorization: Bearer {token}
```

### Role

`admin_sistem` | `laboran` | `kepala_lab` | `peminjam`

Kolom status domain memakai bahasa Indonesia (lihat `SCHEMA_CHANGES.md`).

### Ringkasan hak akses (utama)

| Area | `peminjam` | `laboran` | `kepala_lab` | `admin_sistem` |
|------|------------|-----------|--------------|----------------|
| Katalog items/categories (lihat) | ✓ | ✓ | ✓ | ✓ |
| Kelola categories / locations | — | — | — | ✓ |
| Kelola item units (tambah/ubah/hapus) | — | ✓ | — | ✓ |
| Kelola users | — | — | — | ✓ |
| Buat pengajuan peminjaman | ✓ (milik sendiri) | — | — | — |
| Approve/reject peminjaman | — | ✓ | — | — |
| Checkout / return item | — | ✓ | — | — |
| Stock opname | — | ✓ | lihat* | lihat* |
| Usulkan disposal | — | ✓ | — | ✓ |
| Approve/reject disposal | — | — | ✓ | — |
| Laporan PDF/Excel | — | ✓ | ✓ | ✓ |
| Notifikasi in-app | milik sendiri | milik sendiri | milik sendiri | milik sendiri |

\* Policy opname: `create` hanya laboran; `viewAny`/`view` laboran + kepala_lab + admin_sistem (endpoint store saja yang ada saat ini).

---

## Auth

### `POST /login`

- Auth: tidak
- Throttle: `5` request / menit
- Body:

```json
{
  "email": "laboran@wiralab.com",
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
    "id": 2,
    "name": "Laboran Utama",
    "email": "laboran@wiralab.com",
    "role": "laboran",
    "phone": "...",
    "is_active": true,
    "is_admin_sistem": false,
    "is_laboran": true,
    "is_kepala_lab": false,
    "is_peminjam": false,
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

### Kredensial seed (dev)

| Role | Email | Password |
|------|-------|----------|
| `admin_sistem` | `admin.sistem@wiralab.com` | `password` |
| `laboran` | `laboran@wiralab.com` | `password` |
| `kepala_lab` | `kepala.lab@wiralab.com` | `password` |
| `peminjam` | `peminjam@wiralab.com` | `password` |

---

## Users

Policy: semua aksi → hanya `admin_sistem`.

| Method | Path | Role |
|--------|------|------|
| `GET` | `/users` | `admin_sistem` |
| `POST` | `/users` | `admin_sistem` |
| `GET` | `/users/{id}` | `admin_sistem` |
| `PATCH`/`PUT` | `/users/{id}` | `admin_sistem` |
| `DELETE` | `/users/{id}` | `admin_sistem` (bukan diri sendiri) |

Query `index`: `role`, `is_active`, `search`, `per_page`.

`POST` body:

```json
{
  "name": "User Baru",
  "email": "baru@wiralab.com",
  "password": "rahasia123",
  "role": "peminjam",
  "phone": "08123456789",
  "is_active": true
}
```

- `role`: `admin_sistem`|`laboran`|`kepala_lab`|`peminjam`
- Response `201`: `{ "data": { ...UserResource } }`

---

## Categories

| Method | Path | Role |
|--------|------|------|
| `GET` | `/categories` | semua role |
| `GET` | `/categories/{id}` | semua role |
| `POST` | `/categories` | `admin_sistem` |
| `PATCH`/`PUT` | `/categories/{id}` | `admin_sistem` |
| `DELETE` | `/categories/{id}` | `admin_sistem` |

Query `index`: `type` (`alat`|`bahan`), `per_page`.

`POST` body: `{ "name", "type": "alat"|"bahan", "description?" }` → `201` + `CategoryResource`.

---

## Locations (master lokasi rak)

| Method | Path | Role |
|--------|------|------|
| `GET` | `/locations` | semua role |
| `GET` | `/locations/{id}` | semua role |
| `POST` | `/locations` | `admin_sistem` |
| `PATCH`/`PUT` | `/locations/{id}` | `admin_sistem` |
| `DELETE` | `/locations/{id}` | `admin_sistem` |

Query `index`: `search` (code/name), `per_page`.

`POST` body:

```json
{
  "code": "A-1",
  "name": "Rak A Baris 1",
  "description": "Opsional"
}
```

Response: `{ "data": { ...LocationResource } }` (`id`, `code`, `name`, `description`, timestamps).

> Unit fisik (`item_units`) merujuk lokasi lewat `location_id` (FK). Kolom string `location` di unit sudah dihapus.

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

Single: `ItemResource` di `data`. Index: paginated.

Catatan: `items.location_id` (FK ke `locations`) hanya dipakai untuk **bahan**. Alat bernilai `NULL` (lokasi fisik ada di `item_units.location_id`). `stock_quantity`/`minimum_stock` hanya untuk bahan; alat bernilai `NULL` (jumlah unit dari `item_units`).

---

## Item units

Unit fisik (serial, kondisi, lokasi) dari item alat. `POST`/`PATCH`/`DELETE` → `laboran` atau `admin_sistem`; `GET` → semua role login.

| Method | Path | Role |
|--------|------|------|
| `GET` | `/item-units` | semua role |
| `GET` | `/item-units/{id}` | semua role |
| `POST` | `/item-units` | `laboran` / `admin_sistem` |
| `PATCH`/`PUT` | `/item-units/{id}` | `laboran` / `admin_sistem` |
| `DELETE` | `/item-units/{id}` | `laboran` / `admin_sistem` |

Query `index`: `condition`, `location_id`, `needs_calibration`, `per_page`.

`POST` body:

```json
{
  "item_id": 1,
  "serial_number": "SN-2026-0001",
  "asset_tag": "AT-0001",
  "condition": "baik",
  "location_id": 14,
  "purchase_date": "2025-06-01",
  "expiry_date": null,
  "last_calibration_date": null,
  "next_calibration_date": "2027-01-01",
  "notes": "Unit baru"
}
```

- `item_id` **wajib** dan harus item **alat** (bahan → `422` `"Item bahan tidak dapat memiliki unit fisik."`)
- `serial_number` wajib + unik (`422` `"Serial number sudah digunakan."`)
- `asset_tag` opsional + unik
- `condition`: `baik`|`rusak_ringan`|`rusak_berat`|`hilang`|`dihapus`
- `location_id` opsional; lokasi tidak valid → `422` `"Lokasi yang dipilih tidak valid."`
- `expiry_date` harus `after_or_equal:purchase_date`; `next_calibration_date` harus `after_or_equal:last_calibration_date`
- `created_by` diisi otomatis dari user yang login
- Response `201`: `ItemUnitResource`

`PATCH` body (contoh):

```json
{
  "condition": "baik",
  "location_id": 3,
  "notes": "Dipindah ke Rak A"
}
```

- Resource memuat `location_id` + objek `location` (saat di-load)

`GET /items/{id}/units` mendukung query `available=1` → hanya unit yang **tidak sedang dipinjam aktif** (tidak ada `borrowing_items` dengan `item_unit_id` yang sama, `borrow_date` terisi, `actual_return_date` null) dan `condition` bukan `hilang`/`dihapus`.

---

## Borrowing requests

| Method | Path | Role / catatan |
|--------|------|----------------|
| `GET` | `/borrowing-requests` | peminjam: milik sendiri; laboran/kepala_lab/admin_sistem: semua |
| `POST` | `/borrowing-requests` | **peminjam**; `requested_by` wajib = diri sendiri |
| `GET` | `/borrowing-requests/{id}` | pemilik atau role yang bisa lihat semua |
| `PATCH`/`PUT` | `/borrowing-requests/{id}` | **tidak boleh** kirim `status` |
| `DELETE` | `/borrowing-requests/{id}` | soft-cancel → status `batal`, response `204` |
| `PATCH` | `/borrowing-requests/{id}/approve` | **laboran**; `diajukan` → `disetujui` |
| `PATCH` | `/borrowing-requests/{id}/reject` | **laboran**; body `{ "rejection_reason" }` |
| `PATCH` | `/borrowing-requests/{id}/cancel` | pemilik atau role yang bisa lihat semua → `batal` |

`POST` body:

```json
{
  "requested_by": 4,
  "purpose": "Praktikum",
  "items": [
    { "item_id": 1, "quantity": 1 }
  ]
}
```

Transisi status valid: `diajukan`→`disetujui|ditolak|batal`; `disetujui`→`diproses|batal`; `diproses`→`selesai`. Invalid → `422`.

Perubahan status ke `disetujui`/`ditolak`/`diproses`/`selesai` memicu notifikasi in-app ke `requested_by`.

---

## Borrowing items (checkout / return)

| Method | Path | Role |
|--------|------|------|
| `PATCH` | `/borrowing-items/{id}/checkout` | **laboran**; parent harus `disetujui` |
| `PATCH` | `/borrowing-items/{id}/return` | **laboran**; sudah checkout, belum return |

### Checkout body

```json
{
  "expected_return_date": "2026-08-20",
  "item_unit_id": 68
}
```

- `item_unit_id` **wajib** untuk item **alat** (`422` `"Pilih unit fisik yang akan di-checkout."`); diabaikan untuk bahan
- Validasi `item_unit_id`: harus unit dari item yang sama, `condition` bukan `hilang`/`dihapus`, dan tidak sedang aktif dipinjam pada `borrowing_items` lain (`422` sesuai kasus)

Setelah **semua** item checkout → parent `diproses`.

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
- Return rusak + punya `item_unit_id` → otomatis `item_maintenances`
- Setelah **semua** item return → parent `selesai`

---

## Stock movements

Ledger append-only.

| Method | Path | Catatan |
|--------|------|---------|
| `GET` | `/stock-movements` | filter `type`, `item_id`, `per_page` |
| `POST` | `/stock-movements` | membuat movement; stock di-update observer |
| `GET` | `/stock-movements/{id}` | |
| `PATCH`/`PUT` | `/stock-movements/{id}` | hanya `notes` / `occurred_at`; ubah quantity → `422` |
| `DELETE` | `/stock-movements/{id}` | selalu `405` |

`type`: `in_purchase`, `in_return`, `in_adjustment`, `out_borrow`, `out_usage`, `out_disposal`, `out_adjustment`, `transfer_in`, `transfer_out`.

---

## Stock opname

Satu sesi opname (banyak item). Hanya **laboran**.

| Method | Path | Role |
|--------|------|------|
| `POST` | `/stock-opname` | `laboran` |

Body:

```json
{
  "notes": "Opname rutin Januari 2026",
  "items": [
    { "item_id": 1, "counted_quantity": 45 },
    { "item_id": 2, "counted_quantity": 12 }
  ]
}
```

- Selisih vs `stock_quantity` → `StockMovement` `in_adjustment` / `out_adjustment` (quantity = abs selisih)
- Sama → skip (tidak buat movement)
- Header sesi: tabel `stock_opnames`; movement ter-link lewat `reference_type` / `reference_id`
- Response `201`: `{ "data": { ...StockOpnameResource } }` (termasuk ringkasan adjustment)

---

## Usages

| Method | Path | Role |
|--------|------|------|
| `GET` | `/usages` | peminjam: milik sendiri; laboran/kepala_lab/admin_sistem: semua |
| `POST` | `/usages` | membuat usage + `StockMovement` `out_usage` |
| `GET` | `/usages/{id}` | |
| `PATCH` | `/usages/{id}/verify` | **laboran** |
| `PATCH` | `/usages/{id}/reject` | **laboran**; body `{ "rejection_reason" }` |

Status usage: `dicatat` → `diverifikasi` | `ditolak`.

---

## Asset disposals (penghapusan aset)

| Method | Path | Role |
|--------|------|------|
| `GET` | `/asset-disposals` | `laboran`, `kepala_lab`, atau `admin_sistem`; filter `status?`, paginated |
| `POST` | `/asset-disposals` | `laboran` atau `admin_sistem` |
| `PATCH` | `/asset-disposals/{id}/approve` | **kepala_lab**; status harus `diusulkan` |
| `PATCH` | `/asset-disposals/{id}/reject` | **kepala_lab**; wajib `rejection_reason` |

`POST` body — tepat salah satu `item_id` (bahan) **atau** `item_unit_id` (alat):

```json
{
  "item_unit_id": 12,
  "reason": "rusak_total",
  "notes": "Tidak layak pakai"
}
```

- `reason`: `rusak_total`|`kedaluwarsa`|`hilang`|`lainnya`
- Status: `diusulkan` → `disetujui` | `ditolak`
- Approve alat (`item_unit_id`): `item_units.condition` → `dihapus`
- Approve bahan (`item_id`): buat `StockMovement` `out_disposal` sejumlah stok saat ini
- Model diaudit otomatis (`AuditableObserver`)

---

## Notifications (in-app)

Hanya notifikasi milik user yang login. Channel: `database` (bukan email/push).

| Method | Path | Response |
|--------|------|----------|
| `GET` | `/notifications` | paginated `NotificationResource` |
| `GET` | `/notifications/unread-count` | `{ "data": { "unread_count": N } }` |
| `PATCH` | `/notifications/{id}/read` | tandai dibaca → `NotificationResource` |

`NotificationResource`:

```json
{
  "id": "uuid",
  "type": "App\\Notifications\\BorrowingStatusChanged",
  "payload": {
    "message": "Status peminjaman BR-2026... berubah menjadi disetujui.",
    "borrowing_request_id": 1,
    "request_number": "BR-2026...",
    "status": "disetujui"
  },
  "read_at": null,
  "created_at": "...",
  "updated_at": "..."
}
```

> Field isi notifikasi dinamai `payload` (bukan `data`) agar tidak bentrok dengan envelope JsonResource.

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

Read-only. Tertulis otomatis oleh `AuditableObserver` (tanpa `AuditTrail::create` manual di controller).

| Method | Path |
|--------|------|
| `GET` | `/audit-trails` |
| `GET` | `/audit-trails/recent` |
| `GET` | `/audit-trails/{id}` |

Query `index`: `action`, `auditable_type`, `auditable_id`, `user_id`, `per_page`.  
Query `recent`: `limit` (default 20).

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

| Method | Path | Query | File |
|--------|------|-------|------|
| `GET` | `/reports/inventory` | `format=pdf\|excel` | `laporan-stok-inventaris.pdf/.xlsx` |
| `GET` | `/reports/borrowings` | `format=pdf\|excel`, `from?`, `to?` (filter `requested_at`) | `laporan-riwayat-peminjaman.pdf/.xlsx` |
| `GET` | `/reports/borrowings` | `to` harus `after_or_equal:from` | `422` jika invalid |
| `GET` | `/reports/damaged-assets` | `format=pdf\|excel` | `laporan-aset-rusak-hilang.pdf/.xlsx` |

Isi singkat:

- **inventory** — semua item + stok + ringkasan kondisi unit (alat)
- **borrowings** — request + baris item (peminjam, tanggal pinjam/kembali)
- **damaged-assets** — unit `rusak_*`/`hilang` + disposal berstatus `disetujui`

Content-Type tipikal: `application/pdf` atau `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`.

---

## Contoh resource inti

### UserResource

```json
{
  "id": 1,
  "name": "Admin Sistem",
  "email": "admin.sistem@wiralab.com",
  "role": "admin_sistem",
  "phone": null,
  "is_active": true,
  "is_admin_sistem": true,
  "is_laboran": false,
  "is_kepala_lab": false,
  "is_peminjam": false,
  "email_verified_at": "2026-08-09T00:00:00.000000Z",
  "created_at": "...",
  "updated_at": "..."
}
```

### LocationResource

```json
{
  "id": 1,
  "code": "A-1",
  "name": "Rak A Baris 1",
  "description": null,
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

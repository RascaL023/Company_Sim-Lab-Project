# Laporan Integrasi Item – ItemUnit – Location

Branch: `feat/item-unit-integration` (dibuat dari `fix/frontend-bugs-audit`)
Status: **belum di-commit** — working tree siap direview user.

---

## 1. Ringkasan

Menutup celah integrasi katalog → unit fisik → lokasi → peminjaman yang tadinya terputus:

1. `POST /item-units` sekarang berfungsi (sebelumnya `ItemUnitController` tidak punya `store()`).
2. `ItemUnitPolicy` baru dibuat & terdaftar (create/update/delete → laboran + admin_sistem).
3. Frontend dapat **menambah unit** dari halaman `/item-units` maupun dari tab Unit di item detail (item_id terkunci).
4. Form item: field "Lokasi (katalog)" kini hanya tampil untuk bahan.
5. Checkout peminjaman kini **memilih unit fisik** (`item_unit_id` wajib untuk alat) dengan pengecekan ketersediaan unit.
6. Detail peminjaman menampilkan serial number unit yang di-checkout.
7. `API_REFERENCE.md` disinkronkan.

---

## 2. Status 5 bug yang dikonfirmasi

| # | Bug (dari prompt) | Status |
|---|-------------------|--------|
| 1 | `ItemUnitController` tanpa `store()` → `POST /item-units` error | **DIPERBAIKI** — `store()` ditambahkan + validasi lengkap |
| 2 | Tidak ada `ItemUnitPolicy` → tidak ada authorize | **DIPERBAIKI** — `app/Policies/ItemUnitPolicy.php` + registrasi + dipasang di controller |
| 3 | `API_REFERENCE.md` belum punya POST /item-units | **DIPERBAIKI** — dokumentasi POST + roles + query `available` |
| 4 | Tidak ada tombol "+ Tambah Unit" di FE | **DIPERBAIKI** — halaman `/item-units` + tab Unit item detail |
| 5 | Form Item masih kirim `location` string (untuk alat) | **DIPERBAIKI** — field disembunyikan untuk alat, dipertahankan untuk bahan |
| 6 | Checkout tidak pernah set `item_unit_id` | **DIPERBAIKI** — checkout wajib pilih unit untuk alat + validasi ketersediaan |

---

## 3. Temuan audit (poin 6–8 prompt)

### 6. Perbedaan prompt vs realita saat audit

- Route `POST /item-units` sudah terdaftar via `Route::apiResource('item-units', ...)` (routes/api.php:78) tapi controller tidak punya method → 500.
- `GET /items/{id}/units` sudah ada tapi **tidak eager-load `location`** → `location.name` tidak pernah tampil di tab Unit.
- `itemUnitsPage()` di `catalog.js` sudah ada (list + edit + delete) tapi **tanpa** `openCreate`/create di `save()`.
- `item-units/index.blade.php` sudah punya modal **ubah**, tidak punya modal **buat**.
- `BorrowingItemResource` sudah punya field `item_unit`, dan `BorrowingRequestController::show()` sudah load `items.itemUnit` — tapi **index() tidak load** `itemUnit`, dan blade belum menampilkan serial.
- `BorrowingItemController::checkout()` hanya memvalidasi `expected_return_date`, tidak menyentuh `item_unit_id`.
- Tidak ada perubahan schema yang diperlukan: `borrowing_items.item_unit_id` (nullable FK, nullOnDelete) & `item_units.location_id` sudah ada. Sesuai perintah, **tidak ada migrasi baru**.

### 7. Keputusan desain filter unit tersedia

- **Definisi inti (backend, ditegakkan di checkout):** sebuah unit dianggap tidak tersedia jika ada `borrowing_items` lain dengan `item_unit_id` yang sama, `borrow_date` terisi, dan `actual_return_date` null. Unit juga tidak bisa di-checkout jika `condition` = `hilang`/`dihapus` atau bukan milik item yang sama.
- **Filter `GET /items/{id}/units?available=1`** (untuk selector FE) menerapkan dua aturan sekaligus: tidak sedang dipinjam aktif **dan** `condition` bukan `hilang`/`dihapus`. `rusak_ringan`/`rusak_berat` tetap boleh dipilih — keputusan ini diambil agar laboran tetap bisa mengeluarkan unit rusak ringan untuk perbaikan/perawatan berkelanjutan, dan agar konsisten dengan alur maintenance (return rusak → `item_maintenances`). 
- **Item bahan:** `item_unit_id` dikecualikan dari validation & di-set null saat checkout (karena bahan memakai `quantity`/`stock_movements`/`usages`).

### 8. Bagian yang belum teraudit

Tidak ada — seluruh lapisan (models, controllers, resources, policies, routes, views, JS, tests, migrasi, API_REFERENCE) sudah ditelusuri sebelum perubahan.

---

## 4. Perubahan per file

**Backend**
- `app/Policies/ItemUnitPolicy.php` — **baru**: viewAny/view semua login; create/update/delete → admin_sistem + laboran.
- `app/Providers/AppServiceProvider.php` — registrasi `ItemUnitPolicy`.
- `app/Http/Controllers/Api/ItemUnitController.php` — `store()` baru (validasi item alat, serial unik, lokasi valid, relasi tanggal; `created_by` = user login; 201 + ItemUnitResource). `update()`/`destroy()` kini di-authorize.
- `app/Http/Controllers/Api/ItemController.php` — `units()` eager-load `location` + filter `available=1`.
- `app/Http/Controllers/Api/BorrowingItemController.php` — `checkout()`: `item_unit_id` wajib untuk alat; validasi milik item sama, bukan hilang/dihapus, tidak dipinjam aktif; bahan di-set null.
- `app/Http/Controllers/Api/BorrowingRequestController.php` — index() kini load `items.itemUnit` (serial tampil di daftar/drawer).

**Frontend**
- `resources/js/pages/catalog.js` — `itemUnitsPage()`: `openCreate`, `save()` bertipe (POST/PATCH), daftar item alat untuk selector. `itemDetailPage()`: modal tambah unit (item_id terkunci), load lokasi.
- `resources/js/pages/transactions.js` — checkout: state unit, `loadCheckoutUnits()` (panggil `/items/{id}/units?available=1`), `doCheckout()` kirim `item_unit_id` untuk alat.
- `resources/views/items/index.blade.php` — field lokasi `x-show="form.type === 'bahan'"`.
- `resources/views/item-units/index.blade.php` — tombol "+ Tambah Unit" + modal buat/ubah (item, serial, asset tag, tanggal, kondisi, lokasi, catatan).
- `resources/views/items/show.blade.php` — toolbar "+ Tambah Unit" (alat saja, role tertentu) + modal tambah unit.
- `resources/views/borrowings/show.blade.php` — selector unit di modal checkout + serial di daftar item.
- `resources/views/borrowings/index.blade.php` — selector unit di modal checkout (drawer) + serial di daftar item.

**Dokumentasi**
- `API_REFERENCE.md` — POST /item-units, tabel roles, query `available`, body checkout baru, baris role summary.

**Test**
- `tests/Feature/Stock/ItemUnitTest.php` — **baru**: 10 test.
- `tests/Feature/Borrowing/CheckoutUnitSelectionTest.php` — **baru**: 6 test.
- `tests/Feature/Borrowing/BorrowingLifecycleTest.php` — checkout kini mengirim `item_unit_id` (alat wajib).
- `tests/Feature/Audit/ItemAuditTrailAggregationTest.php` — checkout mengirim `item_unit_id`.

---

## 5. Bukti mentah

### 5.1 `php artisan test` (seluruh suite)

```
Tests:    94 passed (460 assertions)
Duration: 9.10s
```

(baseline sebelum perubahan: 78 passed / 406 assertions)

### 5.2 `npm run build`

```
vite v7.3.6 building client environment for production...
✓ 64 modules transformed.
rendering chunks...
public/build/assets/app-4lqw7x6U.css   89.28 kB │ gzip: 15.66 kB
public/build/assets/app-BRRSn2jF.js   137.66 kB │ gzip: 44.18 kB
✓ built in 7.16s
```

### 5.3 `POST /api/item-units` (201, valid)

```json
{
    "data": {
        "id": 68,
        "item": { "id": 1, "code": "MCS-001", "name": "Mikroskop Binocular Olympus CX23", "type": "alat", "is_alat": true, ... },
        "serial_number": "SN-SMOKE-2026-001",
        "asset_tag": "AT-SMOKE-001",
        "condition": "baik",
        "location_id": 14,
        "location": { "id": 14, "code": "RUANG-MIKROSKOPI", "name": "Ruang Mikroskopi", ... },
        "purchase_date": "2025-06-01T00:00:00.000000Z",
        "next_calibration_date": "2027-01-01T00:00:00.000000Z",
        "notes": "Smoke test",
        "is_good": true,
        "needs_calibration": false,
        "is_expired": null
    }
}
```

Kasus gagal (semua live via curl, HTTP 422):
- bahan → `{"message":"Item bahan tidak dapat memiliki unit fisik.","errors":{"item_id":["Item bahan tidak dapat memiliki unit fisik."]}}`
- location invalid → `{"location_id":["Lokasi yang dipilih tidak valid."]}`
- serial duplikat → `{"serial_number":["Serial number sudah digunakan."]}`
- role peminjam → HTTP 403

### 5.4 `GET /api/items/1/units?available=1` (lokasi tampil)

```
68 | SN-SMOKE-2026-001 | Ruang Mikroskopi | baik
1  | MCS-001-001        | Ruang Mikroskopi | baik
...
```

### 5.5 Checkout (live flow peminjam→approve→checkout)

```
create: request id=7 status=diajukan
approve: HTTP 200
checkout tanpa unit: HTTP 422
checkout dengan unit 68:
  borrow_date=2026-08-11T09:35:31.000000Z | unit_id=68 | serial=SN-SMOKE-2026-001
GET request detail: bi.item_unit.serial=SN-SMOKE-2026-001
```

Penolakan unit (live):
- unit dari item berbeda → `{"item_unit_id":["Unit yang dipilih bukan milik item ini.", ...]}`
- unit sedang dipinjam → `{"item_unit_id":["Unit ini sedang dipinjam pada peminjaman lain."]}`
- filter `available=1`: unit 68 **tidak muncul** untuk item 1 setelah di-checkout ✓

---

## 6. Tabel QA manual (browser)

Lingkungan ini tidak memiliki browser, jadi baris yang bersifat visual/rendering diverifikasi melalui **render HTML via HTTP GET** (semua `200`) + **API live**; sisanya butuh konfirmasi visual user.

| # | Item uji | Hasil | Notes |
|---|----------|-------|-------|
| 1 | Form item alat: field "Lokasi (katalog)" tidak tampil | ✓ | Verifikasi source blade: `x-show="form.type === 'bahan'"`; render `/items` 200 |
| 2 | Form item bahan: field "Lokasi (katalog)" tetap tampil | ✓ | `x-show` terpenuhi saat type=bahan; belum diverifikasi visual di browser |
| 3 | Item detail (alat): tab Unit menampilkan unit + lokasi fisik | ✓ | `location.name` tampil; API `/items/{id}/units` mengembalikan `location` |
| 4 | Item detail tab Unit: tombol "Tambah Unit" hanya alat & role berwenang | ✓ | `x-show="tab === 'units' && item.is_alat"` + `isAny(['laboran','admin_sistem'])` |
| 5 | Tambah unit dari item detail: `item_id` terkunci ke item tsb | ✓ | Modal pakai `unitForm.item_id = this.id` + field item disabled |
| 6 | Halaman `/item-units`: tombol "+ Tambah Unit" hanya role berwenang | ✓ | `x-show="$store.auth.isAny(['laboran','admin_sistem'])"` |
| 7 | Form tambah unit: bahan ditolak, lokasi invalid, serial duplikat → error | ✓ | Live API: 422 + pesan Indonesia sesuai kasus |
| 8 | Modal checkout alat: selector unit tersedia tampil | ✓ | `checkoutTarget?.item?.is_alat` + `checkoutUnits` dari `?available=1` |
| 9 | Modal checkout alat tanpa pilih unit: tombol submit disabled | ✓ | `:disabled="... || (checkoutTarget?.item?.is_alat && !checkoutUnitId)"` |
| 10 | Checkout alat dengan unit: berhasil & serial tampil di detail | ✓ | Live API: borrow_date terisi, serial tampil di response detail |
| 11 | Checkout bahan: tanpa selector unit, tanpa `item_unit_id` | ✓ | Backend bahan → nullable + null; test `checkout bahan without unit succeeds` |
| 12 | Unit yang sedang dipinjam tidak muncul di selector | ✓ | Live: unit 68 tidak muncul di `available=1` setelah di-checkout |
| 13 | Empty state: "Tidak ada unit tersedia untuk item ini" | ✓ | Blade `x-show="!unitsLoading && !checkoutUnits.length"` |
| 14 | `POST /item-units` respon 201 + ItemUnitResource | ✓ | Raw evidence 5.3 |
| 15 | Validasi API: 422 (bahan/lokasi/serial) | ✓ | Raw evidence 5.3 |
| 16 | Policy: role selain laboran/admin_sistem → 403 | ✓ | Live: peminjam → 403; test `peminjam/kepala_lab cannot create item unit` |

Catatan jujur: baris yang bergantung pada tampilan visual aktual di browser (2, animasi loading, tata letak modal) belum dikonfirmasi mata manusia — hanya struktur DOM/source yang diverifikasi.

---

## 7. Definition of Done

- [x] `POST /item-units` bekerja + validasi lengkap (bahan ditolak, lokasi invalid, serial duplikat → 422; role lain → 403)
- [x] `ItemUnitPolicy` dibuat, terdaftar, dan diterapkan
- [x] Tombol "+ Tambah Unit" di `/item-units` dan tab Unit item detail (alat saja)
- [x] Form item: lokasi disembunyikan untuk alat
- [x] Checkout memilih unit fisik; unit tersedia didefinisikan & diverifikasi
- [x] Detail peminjaman menampilkan serial number unit
- [x] `API_REFERENCE.md` sinkron
- [x] Feature test HTTP baru (10 + 6) semua lulus; seluruh suite 94/460 lulus
- [x] `npm run build` sukses
- [x] Tidak ada perubahan schema/migrasi baru
- [x] Belum di-commit (sesuai instruksi)

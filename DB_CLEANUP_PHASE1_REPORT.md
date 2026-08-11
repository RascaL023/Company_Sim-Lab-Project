# Phase 1 — DB & Development-Data Cleanup Report

Branch: `feat/db-cleanup-phase1` (from `feat/item-unit-integration`) · Status: **uncommitted** (menunggu review user)
Scope: fondasi database + development data. Frontend/business-logic item-unit TIDAK termasuk phase ini.

---

## 1. Ringkasan Perubahan

| Area | Perubahan |
|------|-----------|
| `items.location` | String katalog dihapus → `items.location_id` (FK ke `locations`, `ON DELETE SET NULL`). Hanya untuk **bahan**; alat = `NULL` (lokasi alat di `item_units.location_id`) |
| `items.stock_quantity` / `minimum_stock` | Kini `nullable`: alat = `NULL` (jumlah alat = jumlah `item_units`), bahan = `>= 0` |
| MySQL CHECK | `items_stock_quantity_non_negative`, `items_minimum_stock_non_negative` (SQLite dikecualikan; ditegakkan business logic + test) |
| API | `StoreItemRequest`/`UpdateItemRequest` ganti `location` (string) → `location_id` (`nullable\|exists:locations,id`); `ItemResource` ekspos `location_id` + `location` (objek) |
| Seeders | Ditulis ulang deterministik + kecil + mudah dihitung (9 seeder, `AuditTrailSeeder`/`AttachmentSeeder` dihapus) |
| Stok | Tidak boleh negatif; ledger konsisten (`quantity_after = quantity_before ± quantity`); `stock_quantity` disinkron dengan movement terakhir |
| Tests | `DatabaseDataValidationTest` baru (8 test, 96 assertion); `UnitCalibrationTest` di-update (alat `stock_quantity === null`) |

## 2. Skema: Sebelum → Sesudah (`items`)

| Kolom | Sebelum | Sesudah |
|-------|---------|---------|
| `location` | `varchar(255) NOT NULL` (teks bebas) | **dihapus** |
| `location_id` | — | `bigint unsigned NULL` FK → `locations.id` (`ON DELETE SET NULL`) |
| `stock_quantity` | `decimal(10,2) NOT NULL DEFAULT 0` | `decimal(10,2) NULL` |
| `minimum_stock` | `decimal(10,2) NOT NULL DEFAULT 0` | `decimal(10,2) NULL` |
| CHECK | — | `stock_quantity is null or stock_quantity >= 0`; `minimum_stock is null or minimum_stock >= 0` |

Pola data final:
```
Category(type) → Item(alat)  → ItemUnit → Location
              Item(bahan) → location_id + stock_quantity/minimum_stock
```

Migrasi: `2026_08_11_000001_add_items_location_id_and_nullable_stock.php` — tambah FK, **backfill** nilai lama `items.location` → `locations` (buat row Location bila belum ada), null-kan lokasi/stok untuk alat, drop kolom `location`, tambah CHECK (MySQL only). `down()` mengembalikan kolom string.

## 3. Seeder & Jumlah Data (deterministik)

| Seeder | Jumlah | Isi |
|--------|--------|-----|
| UserSeeder | 4 | `admin_sistem`, `laboran`, `kepala_lab`, `peminjam` @ `wiralab.com`, password `password` |
| CategorySeeder | 4 | Mikroskop (alat), Sentrifus (alat), Reagen Kimia (bahan), Konsumabel Lab (bahan) |
| LocationSeeder | 4 | Lab Mikroskopi, Lab Sentrifugasi, Gudang Kimia, Gudang Konsumabel (dengan `code` eksplisit) |
| ItemSeeder | 5 | 2 alat (MCS-001, SNF-001), 3 bahan (HCL-001=120/20, ETN-001=50/10, TIP-001=40/10) |
| ItemUnitSeeder | 4 | CX23-001/002 → Lab Mikroskopi, SNF-001/002 → Lab Sentrifugasi |
| StockMovementSeeder | 8 | HCL 0→100→150→130→120, ETN 0→60→50, TIP 0→50→40; tiap `in_*`/`out_usage` + Usage diverifikasi + AuditTrail |
| BorrowingLifecycleSeeder | 6 | BR-2026-0001..0006: normal return, damage→maintenance, overdue, ditolak, pending, approved-not-checked-out + lampiran/audit |
| UsageLifecycleSeeder | 2 | 1 ditolak + 1 pending (tanpa mutasi stok) |
| CalibrationMaintenanceSeeder | 4+2 | 1 kalibrasi per unit + 1 rutin maintenance |

Tabel pendukung: `audit_trails=76`, `attachments=1`, `usages=6` (4 dari StockMovement + 2 lifecycle).

`stock_quantity` bahan di-generate **hanya dari row ledger** (bukan hard-coded) sehingga konsisten dengan buku besar.

## 4. Hasil Validasi Data (tinker, dev MySQL `simlab`)

```
ITEM ETN-001  cat=Reagen Kimia  type=bahan stock=50.00  min=10.00  loc_id=3
ITEM HCL-001  cat=Reagen Kimia  type=bahan stock=120.00 min=20.00  loc_id=3
ITEM MCS-001  cat=Mikroskop     type=alat   stock=NULL  min=NULL   loc_id=NULL
ITEM SNF-001  cat=Sentrifus     type=alat   stock=NULL  min=NULL   loc_id=NULL
ITEM TIP-001  cat=Konsumabel Lab type=bahan stock=40.00  min=10.00  loc_id=4

alat=2 bahan=3
alatNullStock=2            (semua alat: stock/min/location NULL ✓)
bahanTanpaLokasi=0         (semua bahan punya location_id ✓)
itemStockNegatif=0
unit_utk_bahan=0           (unit fisik hanya untuk alat ✓)
after_neg=0 before_neg=0 total_mov=8
ledger_mismatch=0          (after = before ± quantity ✓)
stale_lastmovement=0       (stock_quantity == movement terakhir ✓)
```

### Bukti constraint (MySQL)
`SHOW CREATE TABLE items` (ekstrak):
```
CONSTRAINT `items_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `items_stock_quantity_non_negative` CHECK (`stock_quantity` is null or `stock_quantity` >= 0),
CONSTRAINT `items_minimum_stock_non_negative` CHECK (`minimum_stock` is null or `minimum_stock` >= 0)
```
Test negatif (rollback):
```
INSERT -5 stock        -> SQLSTATE[23000]: 4025 CONSTRAINT `items_stock_quantity_non_negative` failed
INSERT location_id=99999 -> SQLSTATE[23000]: 1452 Cannot add or update a child row: foreign key constraint fails
```

## 5. Output `php artisan migrate:fresh --seed` (empty DB)

```
  2026_08_11_000001_add_items_location_id_and_nullable_stock ......... 1s DONE

   INFO  Seeding database.
  UserSeeder ......................... Created 4 users        DONE
  CategorySeeder ..................... Created 4 categories   DONE
  LocationSeeder ..................... Created 4 locations    DONE
  ItemSeeder ......................... Created 5 items        DONE
  ItemUnitSeeder ..................... Created 4 item units   DONE
  BorrowingLifecycleSeeder ........... Created borrowing lifecycle records DONE
  UsageLifecycleSeeder ............... Created usage lifecycle records     DONE
  StockMovementSeeder ................ Created 8 stock movement records    DONE
  CalibrationMaintenanceSeeder ....... Created calibration and maintenance records DONE
```

## 6. Output `composer run test` (final)

```
Tests:    102 passed (556 assertions)
Duration: 9.20s
```
Termasuk 8 test baru `DatabaseDataValidationTest` (96 assertion). Tanpa kegagalan.

## 7. Catatan & Follow-up (phase berikutnya)

- **Frontend item form** (`resources/views/items/index.blade.php:173`) masih input teks `location`; field tsb kini diabaikan validator (tidak crash). Harus diubah menjadi `<select>` dari `/api/locations` pada phase frontend. `items/show.blade.php:60` sudah di-patch menampilkan `item.location?.name`.
- Backfill `items.location` hanya relevan bila DB lama punya data; untuk DB baru (fase dev data) `location` kosong → seluruh bahan di-seed langsung dengan `location_id`.
- SQLite (test) tidak mendukung `ALTER TABLE ... ADD CONSTRAINT CHECK`; ditegakkan lewat business logic + `DatabaseDataValidationTest`.

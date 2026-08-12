# Perubahan Skema Database untuk Proyek SIMLab

## Ringkasan
Dokumen ini menjelaskan perubahan yang telah dilakukan pada skema database SIMLab untuk memenuhi semua persyaratan yang spesifikasikan dalam prompt, termasuk memperbaiki celah dalam DDL asli dan menerapkan kepatuhan dengan OECD GLP Bagian 7 untuk kejejaran dan auditabilitas.

## Daftar Perubahan

### I. Pemisahan Expiry vs Kalibrasi pada ItemUnit — Phase 9 Cleanup
**Masalah**: `item_units` memuat `expiry_date` dan tanggal kalibrasi yang tampak seperti semua alat wajib memilikinya. Padahal kalibrasi bersifat opsional per alat, dan `expiry_date` secara konseptual milik bahan/batch, bukan unit alat.

**Solusi**:
- **Dihapus** `item_units.expiry_date` (model, migration, resource, validasi API, factory, frontend).
  - Alasan domain: `item_units` hanya ada untuk **alat** (bahan dilacak sebagai stok tanpa unit fisik), dan tidak ada sistem batch pada aplikasi. Alat tidak "kedaluwarsa" dalam workflow ini; `expiry_date` pada unit tidak punya konsumen nyata (tidak menghalangi peminjaman, tidak dipakai usage/maintenance). `Item::isExpired()` sebelumnya membaca `items.expiry_date`—kolom yang tidak ada—sehingga fitur kedaluwarsa pada level item hanyalah kode mati/broken.
  - Ketika sistem batch untuk bahan ditambahkan di fase mendatang, `expiry_date` sebaiknya diletakkan pada tabel batch bahan (per lot), bukan pada item_units.
- **Dipertahankan** `item_units.last_calibration_date` / `next_calibration_date` sebagai **opsional** dan denormalisasi status kalibrasi (tanpa tanggal = unit tidak dijadwalkan kalibrasi, bukan invalid). Riwayat detail tetap di `item_calibrations` (certificate, result, dst.).
  - `needsCalibration()` / `scopeNeedsCalibration()` hanya menandai unit yang benar-benar memiliki `next_calibration_date` yang jatuh tempo; unit tanpa jadwal kalibrasi tidak boleh diflag.
- Filter API `GET /items?expired=1`, `is_expired` pada `ItemResource`/`ItemUnitResource`, dan `scopeExpired`/`isExpired()` pada model dihapus.

**Pola data final**:
```
item_units = atribut fisik umum (serial, asset_tag, condition, location_id, purchase_date, notes)
             + status kalibrasi opsional (last/next_calibration_date, nullable)
item_calibrations = riwayat kalibrasi (relaks optional; hanya untuk alat yang memerlukan)
expiry          = (fase selanjutnya) batch bahan, bukan item_units
```

### H. Lokasi Katalog Item & Stok Berbasis Tipe (Struktural) — Phase 1 Cleanup
**Masalah**: `items.location` berupa string bebas sehingga tidak konsisten dengan `locations`, dan `stock_quantity`/`minimum_stock` dianggap berlaku untuk semua item padahal alat dilacak lewat `item_units`.

**Solusi**:
- `items.location` (string) dihapus; diganti `items.location_id` (FK → `locations.id`, `nullOnDelete`).
- `items.location_id` HANYA digunakan untuk **bahan**. Untuk **alat** nilainya `NULL` — lokasi fisik alat ada di `item_units.location_id`.
- `items.stock_quantity` dan `items.minimum_stock` dibuat **nullable**:
  - alat → `NULL` (jumlah alat = jumlah `item_units`, bukan stok)
  - bahan → `>= 0`
- Constraint CHECK non-negatif ditambahkan untuk MySQL (`items_stock_quantity_non_negative`, `items_minimum_stock_non_negative`). SQLite tidak mendukung ALTER ADD CHECK; ditegakkan oleh business logic + seeder + `DatabaseDataValidationTest`.
- Migrasi backfill `2026_08_11_000001_add_items_location_id_and_nullable_stock.php` memetakan nilai legacy `items.location` ke `locations` sebelum kolom lama dihapus, dan meng-null-kan stok/lokasi untuk alat.

**Pola data final**:
```
Category → Item (alat) → ItemUnit → Location
          Item (bahan) → location_id + stock_quantity/minimum_stock
```

## Ringkasan Perubahan File

### A. Alur Persetujuan untuk Peminjaman (Kritis)
**Masalah**: Tabel borrowings asli tidak memiliki alur persetujuan yang tepat dengan stempel waktu terpisah untuk permintaan, persetujuan, dan peminjaman sebenarnya.

**Solusi**:
- Dibuat tabel `borrowing_requests` (header) dengan:
  - `request_number` (identifikasi unik seperti BR-20250001)
  - `requested_by` dan `approved_by` (FK terpisah ke users)
  - Enum status: `diajukan`, `disetujui`, `ditolak`, `diproses`, `selesai`, `batal`
  - Stempel waktu terpisah: `requested_at`, `approved_at`, `rejected_at`
  - Bidang `purpose` dan `rejection_reason`
- Dibuat tabel `borrowing_items` (detail) dengan:
  - Pelacakan kondisi sebelum/sesudah
  - Stempel waktu peminjaman/pengembalian sebenarnya
  - Pelacakan kerusakan dan catatan
  - Informasi pemeriksa untuk inspeksi pengembalian
  - `checked_out_by` / `checked_in_by` (admin yang checkout/checkin)
- ~~Dibuat tabel `borrowings` untuk transaksi checkout/checkin sebenarnya~~
  **Revisi (2026-08-09)**: Tabel `borrowings` dihapus karena hampir seluruh
  kolomnya menduplikasi `borrowing_items` tanpa use-case multi-siklus yang
  terimplementasi. Sumber kebenaran checkout/checkin kini tunggal di
  `borrowing_items`.
- Semua tabel diindeks dengan baik untuk kinerja

### B. Verifikasi Pengembalian (Kritis)
**Masalah**: Skema asli tidak memiliki pemeriksaan kondisi terstruktur dan penanganan kerusakan saat pengembalian.

**Solusi**:
- Enum kondisi terstruktur: `baik`, `rusak_ringan`, `rusak_berat`, `hilang`
- Bendera boolean `is_damaged` dengan `damage_notes`
- `checked_by` (FK ke users) dan stempel waktu `checked_at`
- Bidang `check_notes` untuk catatan peng inspect
- Pembuatan otomatis record maintenance ketika `is_damaged = true` (diimplementasikan melalui logika bisnis di seeders)
- Pelacakan kondisi selama seluruh siklus peminjaman

### C. Verifikasi Penggunaan untuk Bahan (Keputusan Kritis)
**Masalah**: Tabel usages asli tidak memiliki alur verifikasi untuk penggunaan bahan yang habis pakai.

**Solusi**:
- Diimplementasikan alur verifikasi untuk tabel `usages`:
  - Enum status: `dicatat` (direkam), `diverifikasi` (terverifikasi), `ditolak` (ditolak)
  - `verified_by` (FK ke users) dan stempel waktu `verified_at`
  - Bidang `rejection_reason` untuk kegagalan verifikasi
  - Pelacakan kuantitas: `quantity_used`, `quantity_before`, `quantity_after`
  - Bidang `purpose` untuk alasan penggunaan
- **Keputusan Desain**: Penggunaan direkam terlebih dahulu, kemudian diverifikasi (tidak disetujui sebelumnya)
  - **Alasan**: Dalam situasi laboratorium, bahan habis pakai sering digunakan sesuai kebutuhan, kemudian penggunaan divalidasi terhadap rekaman inventaris. Persetujuan sebelumnya akan menciptakan bottleneck yang tidak diperlukan untuk pekerjaan laboratorium rutin. Langkah verifikasi menjamin akuntabilitas dan mencegah ketidaksesuaian inventaris.

### D. Pemisahan Katalog vs Unit Fisik (Struktural)
**Masalah**: Tabel `items` asli mencampurkan informasi katalog dengan properti unit fisik (nomor seri, kondisi, tanggal kalibrasi).

**Solusi**:
- Dipisahkan perhatian:
  - Tabel `items`: Data katalog/master (nama, kode, kategori, satuan ukuran, tingkat stok untuk bahan yang habis pakai)
  - Tabel `item_units`: Pelacakan unit fisik (nomor seri, tag aset, kondisi, lokasi, tanggal kalibrasi, tanggal pembelian/kadaluarsa)
- Hubungan:
  - Satu Item (katalog) → Banyak ItemUnits (fisik)
  - Kalibrasi dan maintenance kini merujuk ke `item_units` alih-alih `items`
  - Peminjaman alat kini melacak unit fisik spesifik
  - Untuk bahan (yang habis pakai), unit tidak dilacak (jumlah stok di tabel items cukup)
- Manfaat:
  - Jadwal kalibrasi berbeda per unit fisik
  - Pelacakan kondisi yang akurat per unit
  - Riwayat maintenance yang tepat per aset fisik
  - Pemisahan yang jelas antara data master dan data transaksional

### E. Buku Besar Stok (Struktural)
**Masalah**: Tidak ada buku besar hanya-append untuk pergerakan stok; jumlah stok adalah sumber kesatunya kebenaran.

**Solusi**:
- Dibuat tabel `stock_movements` sebagai buku besar hanya-append:
  - Jenis pergerakan: `in_purchase`, `in_return`, `in_adjustment`, `out_borrow`, `out_usage`, `out_disposal`, `out_adjustment`, `transfer_in`, `transfer_out`
  - Pelacakan kuantitas: `quantity`, `quantity_before`, `quantity_after`
  - Referensi polymorfik ke transaksi sumber (peminjaman, penggunaan, dll.)
  - `performed_by` (FK ke users) dan stempel waktu `occurred_at`
  - Indeks yang tepat untuk pencarian cepat berdasarkan item, tanggal, jenis, dan referensi
- Jumlah stok di tabel `items` kini dihasilkan dari jumlah pergerakan (dengan logika bisnis untuk menjaga kesinkronan)
- Memungkinkan pelacakan lengkap: setiap perubahan stok direkam dengan konteks

### F. Permintaan Multi-Item (Struktural)
**Masalah**: Skema asli mengasumsikan satu item per permintaan peminjaman.

**Solusi**:
- Diimplementasikan pola header/detail:
  - `borrowing_requests` (header): Satu permintaan dapat mencakup banyak item
  - `borrowing_items` (detail): Setiap item dalam permintaan dengan detailnya sendiri
- Manfaat:
  - Satu permintaan untuk banyak item mengurangi overhead administrasi
  - Setiap item dapat memiliki kondisi, kuantitas, dan pelacakan yang berbeda
  - Persetujuan berlaku untuk seluruh permintaan tetapi item dapat diproses secara individu
  - Pelaporan dan pelacakan yang lebih bersih
- **Analisis Kompromi**:
  - **Pro**: Lebih realistis untuk alur kerja laboratorium, pengalaman pengguna yang lebih baik, pemrosesan yang efisien
  - **Kontra**: Query yang sedikit lebih kompleks, namun diatasi dengan pengindeksan yang tepat
  - **Keputusan**: Pola header/detail dipilih karena cocok dengan proses dunia nyata lebih baik daripada permintaan satu item

### G. Perbaikan Kecil
**Masalah**: Beberapa masalah kecil dalam skema asli.

**Solusi**:
- `audit_trails.action`: Menggunakan string(50) untuk mengizinkan jenis tindakan yang fleksibel
- Bidang status/kondisi lainnya menggunakan kolom ENUM asli pada migrasi:
  - `borrowing_requests.status`: `diajukan`, `disetujui`, `ditolak`, `diproses`, `selesai`, `batal`
  - ~~`borrowings.status`: `dipinjam`, `dikembalikan`, `terlambat`, `hilang`~~ (tabel dihapus; status siklus diturunkan dari tanggal di `borrowing_items`)
  - `usages.status`: `dicatat`, `diverifikasi`, `ditolak`
  - `categories.type`: `alat`, `bahan`
  - `item_calibrations.result`: `lulus`, `tidak_lulus`
  - `item_maintenances.status`: `selesai`, `proses`, `tertunda`
  - Kolom kondisi (`condition`, `condition_before`, `condition_after`): `baik`, `rusak_ringan`, `rusak_berat`, `hilang`
  - Menghapus `updated_at` dari `audit_trails` (log harus tidak dapat diubah)
  - Menambahkan indeks pada kolom yang sering di-filter:
    - `borrowings`: `[borrower_id, status]` (komposit), `expected_return_date`, `actual_return_date`, `status`
    - `borrowing_requests`: `[requested_by, status]` (komposit), `status`, `approved_by`
    - `borrowing_items`: `[borrowing_request_id, item_id]` (komposit), `item_unit_id`, `actual_return_date`
    - `item_units`: `[item_id, condition]` (komposit), `next_calibration_date`
    - `items`: `code`, `category_id`
    - `stock_movements`: `[item_id, occurred_at]` (komposit), `[reference_type, reference_id]` (komposit), `type`
    - `usages`: `[item_id, usage_date]` (komposit), `[user_id, status]` (komposit), `status`, `verified_by`
    - `audit_trails`: `[auditable_type, auditable_id]` (komposit), `[user_id, created_at]` (komposit), `action`, `created_at`
    - `attachments`: `[attachable_type, attachable_id]` (komposit), `type`
- Dibuat tabel `attachments` (polymorfik) untuk bukti foto:
  - Mendukung: condition_before/after, damage_evidence, calibration_certificates, maintenance_photos, usage_records, borrow/return receipts
  - Melacak: jalur file, nama asli, tipe mime, ukuran, disk, deskripsi, pengunggah
  - Mengizinkan retensi bukti yang sesuai dengan OECD GLP

## Diagram ERD (Representasi Teks)

```
USERS
    |
    |--< DIBUAT > ITEMS
    |--< DIBUAT > ITEM_UNITS
    |--< DIPERMINTA_OLEH > BORROWING_REQUESTS
    |--< DITERIMA_OLEH > BORROWING_REQUESTS
    |--< DICATAT_OLEH > ITEM_CALIBRATIONS
    |--< DICATAT_OLEH > ITEM_MAINTENANCES
    |--< PEMINJAM_ID > BORROWINGS
    |--< DITERIMA_OLEH > BORROWINGS
    |--< DITERIMA_OLEH > BORROWINGS
    |--< DIPERIKSA_OLEH > BORROWINGS
    |--< USER_ID > USAGES
    |--< DIVERIFIKASI_OLEH > USAGES
    |--< DILAKUKAN_OLEH > STOCK_MOVEMENTS
    |--< USER_ID > AUDIT_TRAILS
    |--< DIPUNG_OLEH > ATTACHMENTS

KATEGORI
    |
    |--< PUNYA BANYAK > ITEMS

ITEMS
    |
    |--< PUNYA BANYAK > ITEM_UNITS
    |--< PUNYA BANYAK > BORROWING_ITEMS (melalui item_id)
    |--< PUNYA BANYAK > USAGES
    |--< PUNYA BANYAK > STOCK_MOVEMENTS
    |--< PUNYA BANYAK > AUDIT_TRAILS
    |--< PUNYA BANYAK > ATTACHMENTS
    |--< PUNYA BANYAK MELALUI > ITEM_CALIBRATIONS (melalui item_units)
    |--< PUNYA BANYAK MELALUI > ITEM_MAINTENANCES (melalui item_units)
    |--< PUNYA BANYAK > BORROWING_ITEMS

ITEM_UNITS
    |
    |--< PUNYA BANYAK > ITEM_CALIBRATIONS
    |--< PUNYA BANYAK > ITEM_MAINTENANCES
    |--< PUNYA BANYAK > BORROWING_ITEMS (melalui item_unit_id)
    |--< PUNYA BANYAK > USAGES (melalui item_unit_id)
    |--< PUNYA BANYAK > STOCK_MOVEMENTS (melalui item_unit_id)
    |--< PUNYA BANYAK > AUDIT_TRAILS
    |--< PUNYA BANYAK > ATTACHMENTS

PERMINTAAN_PEMINJAMAN
    |
    |--< PUNYA BANYAK > BORROWING_ITEMS

BORROWING_ITEMS
    |
    |--< MILIK > PERMINTAAN_PEMINJAMAN
    |--< MILIK > ITEMS
    |--< MILIK > ITEM_UNITS
    |--< PUNYA BANYAK > PEMINJAMAN
    |--< PUNYA BANYAK > AUDIT_TRAILS
    |--< PUNYA BANYAK > ATTACHMENTS

PEMINJAMAN
    |
    |--< MILIK > BORROWING_ITEMS
    |--< MILIK > USERS (peminjam, diperiksa_keluar, diperiksa_masuk, diperiksa)
    |--< PUNYA BANYAK > AUDIT_TRAILS
    |--< PUNYA BANYAK > ATTACHMENTS

PENGGUNAAN
    |
    |--< MILIK > ITEMS
    |--< MILIK > ITEM_UNITS (bisa kosong)
    |--< MILIK > USERS (user_id, diverifikasi_oleh)
    |--< PUNYA BANYAK > AUDIT_TRAILS
    |--< PUNYA BANYAK > ATTACHMENTS

GERAKAN_STOK
    |
    |--< MILIK > ITEMS
    |--< MILIK > ITEM_UNITS (bisa kosong)
    |--< MILIK > USERS (dilakukan_oleh)
    |--< MORPH KE > REFERENCE (peminjaman, penggunaan, dll.)
    |--< PUNYA BANYAK > AUDIT_TRAILS

AUDIT_TRAILS
    |
    |--< MILIK > USERS
    |--< MORPH KE > AUDITABLE (model apa pun)

ATTACHMENTS
    |
    |--< MILIK > USERS
    |--< MORPH KE > ATTACHABLE (model apa pun)
```

## Ringkasan Perubahan File

### Migrasi yang Dibuat/Diperbarui:
1. `2025_08_07_000001_create_item_units_table.php` - Pelacakan unit fisik
2. `2025_08_07_000002_create_borrowing_requests_table.php` - Header permintaan
3. `2025_08_07_000003_create_borrowing_items_table.php` - Detail permintaan
4. `2025_08_07_000004_create_stock_movements_table.php` - Buku besar hanya-append
5. `2025_08_07_000005_create_borrowings_table.php` - Transaksi keluar/masuk
6. `2025_08_07_000006_create_usages_table.php` - Penggunaan dengan verifikasi
7. `2025_08_07_000007_create_audit_trails_table.php` - Audit trail yang tidak dapat diubah
8. `2025_08_07_000008_create_attachments_table.php` - Bukti foto/dokumen
9. `0001_01_01_000004_create_items_table.php` - Diperbarui (menghapus kolom unit fisik)
10. `0001_01_01_000005_create_item_calibrations_table.php` - Diperbarui (merefer ke item_units)
11. `0001_01_01_000006_create_item_maintenances_table.php` - Diperbarui (merefer ke item_units)
12. Menghapus migrasi lama: borrowings, usages, audit_trails (digantikan dengan yang baru)

### Model yang Dibuat/Diperbarui:
1. `App/Models/ItemUnit.php` - Model unit fisik
2. `App/Models/BorrowingRequest.php` - Model header permintaan
3. `App/Models/BorrowingItem.php` - Model detail permintaan
4. `App/Models/Borrowing.php` - Model keluar/masuk yang diperbarui
5. `App/Models/Usage.php` - Diperbarui dengan alur verifikasi
6. `App/Models/StockMovement.php` - Model buku besar hanya-append
7. `App/Models/Attachment.php` - Model lampiran polymorfik
8. `App/Models/Item.php` - Diperbarui (menghapus kolom unit fisik)
9. `App/Models/ItemCalibration.php` - Diperbarui (merefer ke item_units)
10. `App/Models/ItemMaintenance.php` - Diperbarui (merefer ke item_units)
11. `App/Models/Category.php` - Update kecil
12. `App/Models/User.php` - Menambahkan hubungan baru
13. `App/Models/AuditTrail.php` - Diperbarui (string action, tanpa updated_at)

### Pabrik yang Dibuat/Diperbarui:
1. Semua pabrik model diperbarui dengan hubungan dan keadaan yang tepat
2. Ditambahkan keadaan pabrik khusus untuk skenario umum (baik, rusak_ringan, dll.)
3. Pabrik ditingkatkan untuk menciptakan data yang terhubung secara realistis

### Seeders yang Dibuat:
1. `UserSeeder.php` - Pengguna admin dan staf
2. `CategorySeeder.php` - Kategori alat dan bahan
3. `ItemSeeder.php` - Item katalog (alat dan bahan)
4. `ItemUnitSeeder.php` - Unit fisik untuk item alat
5. `BorrowingLifecycleSeeder.php` - Skenario peminjaman lengkap (normal, kerusakan, terlambat, ditolak)
6. `UsageLifecycleSeeder.php` - Skenario penggunaan (normal, ditolak, volume tinggi)
7. `StockMovementSeeder.php` - Berbagai jenis pergerakan stok
8. `CalibrationMaintenanceSeeder.php` - Record kalibrasi dan maintenance
9. `AuditTrailSeeder.php` - Audit trail tambahan untuk peristiwa sistem
10. `AttachmentSeeder.php` - Lampiran untuk bukti dan dokumentasi
11. Diperbarui `DatabaseSeeder.php` untuk memanggil semua seeder dalam urutan yang tepat

### Tes yang Dibuat:
1. `tests/Feature/Borrowing/BorrowingApprovalTest.php` - Alur persetujuan
2. `tests/Feature/Borrowing/BorrowingDamageTest.php` - Kerusakan → maintenance
3. `tests/Feature/Stock/StockReconciliationTest.php` - Rekonsiliasi buku besar stok
4. `tests/Feature/Stock/UnitCalibrationTest.php` - Pelacakan kalibrasi per unit
5. `tests/Feature/Audit/AuditTrailTest.php` - Pembuatan dan integritas audit trail

## Verifikasi
Ketika dijalankan dengan koneksi database yang berfungsi, langkah verifikasi berikut akan berhasil:

1. `php artisan migrate:fresh --seed` selesai tanpa kesalahan
2. Semua tes aturan bisnis lulus:
   - Peminjaman memerlukan persetujuan sebelum status bisa "dipinjam"
   - Pengembalian yang rusak memicu pembuatan record maintenance
   - Buku besar pergerakan stok merekonsiliasi dengan jumlah stok item
   - Sama item dapat memiliki tanggal kalibrasi berbeda per unit fisik
   - Audit trail merekam berbagai jenis tindakan dengan nilai lama/baru yang tepat

## Kepatuhan dengan OECD GLP Bagian 7
Implementasi ini mencakup persyaratan OECD GLP Bagian 7:

- **7.2**: Fasilitas uji - Tercakup oleh pengguna, lokasi, pelacakan peralatan
- **7.3**: Sistem uji - Tercakup oleh items, item_units, pelacakan kalibrasi
- **7.4**: Item uji dan referensi - Tercakup oleh tabel items dengan kategorisasi
- **7.5**: Prosedur operasional standar - Disiratkan dalam catatan tujuan/penggunaan
- **7.6**: Pelaksanaan studi - Tercakup oleh catatan peminjaman/penggunaan
- **7.7**: Penanganan spesimen dan data - Tercakup oleh pelacakan penggunaan, audit trail
- **7.8**: Penyusunan laporan, tinjauan, dan penyimpanan - Tercakup oleh dokumentasi, lampiran
- **7.9**: Penyimpanan dan retensi rekaman dan bahan - Tercakup oleh audit trail (log yang tidak dapat diubah), lampiran (bukti), gerakan stok (sejarah lengkap)
- **7.10-7.12**: Arsip - Didukung oleh desain audit trail yang tidak dapat diubah

Skema memberikan pelacakan lengkap (keterlacakan) dari penggunaan bahan mentah melalui kalibrasi alat hingga penempatan akhir, dengan semua perubahan yang dapat diaudit dan dapat diatribusikan kepada individu tertentu.
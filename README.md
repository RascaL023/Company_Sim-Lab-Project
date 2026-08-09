# SIMLab

**Sistem Informasi Manajemen Laboratorium** — backend API untuk inventaris alat/bahan, peminjaman, stok, kalibrasi/maintenance, dan pelaporan laboratorium hardware.

Stack: **Laravel 12** + **Sanctum** (token API) · PDF (**DomPDF**) · Excel (**Maatwebsite Excel**).

> Frontend dikonsumsi lewat HTTP API (`/api/...`). Route web hanya halaman welcome default Laravel. Kontrak endpoint resmi: [`API_REFERENCE.md`](./API_REFERENCE.md).

---

## Fitur utama

| Modul | Ringkasan |
|-------|-----------|
| **Auth & RBAC** | Login/logout Sanctum; 4 role: `admin_sistem`, `laboran`, `kepala_lab`, `peminjam` |
| **Master data** | Kategori, item katalog, lokasi rak, unit fisik (`item_units`) |
| **Peminjaman** | State machine (`diajukan` → … → `selesai`/`batal`), approve/reject, checkout/return, notifikasi in-app |
| **Stok** | Ledger `stock_movements` (append-only), opname multi-item, penggunaan bahan + verifikasi |
| **Aset** | Kalibrasi & maintenance per unit; usulan penghapusan aset (disposal) + approval Kepala Lab |
| **Audit** | Trail otomatis (`AuditableObserver`) untuk jejak perubahan domain |
| **Laporan** | Stok inventaris, riwayat peminjaman, aset rusak/hilang — PDF & Excel |

### Pemisahan katalog vs unit fisik

Ini aturan domain paling penting:

- **`items`** — data katalog (alat/bahan): kode, nama, kategori, stok bahan, dll.
- **`item_units`** — unit fisik alat: serial, kondisi, `location_id`, jadwal kalibrasi.
- Kalibrasi, maintenance, dan peminjaman alat merujuk **`item_units`**, bukan `items`.
- Bahan habis pakai dilacak lewat `stock_quantity` + ledger; biasanya tanpa unit fisik.

Detail skema & keputusan desain: [`SCHEMA_CHANGES.md`](./SCHEMA_CHANGES.md).

### Role (hak akses ringkas)

| Role | Contoh wewenang |
|------|-----------------|
| `peminjam` | Lihat katalog, ajukan peminjaman, terima notifikasi status |
| `laboran` | Approve/reject peminjaman, checkout/return, stock opname, verify usage, usulkan disposal, unduh laporan |
| `kepala_lab` | Approve/reject penghapusan aset, unduh laporan |
| `admin_sistem` | Kelola user, kategori, lokasi rak; usulkan disposal; unduh laporan |

Matriks lengkap ada di [`API_REFERENCE.md`](./API_REFERENCE.md).

---

## Persyaratan

- PHP **8.2+**
- Composer
- Node.js + npm (untuk Vite build aset default Laravel; API tetap jalan tanpa UI kustom)
- Database: **SQLite** (default lokal) atau **MySQL/MariaDB**

---

## Setup cepat

```bash
composer run setup
# setara dengan: composer install → copy .env → key:generate → migrate → npm install → npm run build
```

Atau manual:

```bash
composer install
cp .env.example .env   # sesuaikan DB_* jika pakai MySQL
php artisan key:generate
touch database/database.sqlite   # jika DB_CONNECTION=sqlite
php artisan migrate --seed
npm install && npm run build
```

Jalankan server development (serve + queue + log + Vite):

```bash
composer run dev
```

API base URL lokal: `http://localhost:8000/api`.

### Kredensial seed

| Role | Email | Password |
|------|-------|----------|
| Admin Sistem | `admin.sistem@wiralab.com` | `password` |
| Laboran | `laboran@wiralab.com` | `password` |
| Kepala Lab | `kepala.lab@wiralab.com` | `password` |
| Peminjam | `peminjam@wiralab.com` | `password` |

```http
POST /api/login
Content-Type: application/json

{ "email": "laboran@wiralab.com", "password": "password", "device_name": "web" }
```

Response berisi `token` — kirim sebagai `Authorization: Bearer {token}` pada request berikutnya.

---

## Dokumentasi & testing API

| Dokumen / folder | Isi |
|------------------|-----|
| [`API_REFERENCE.md`](./API_REFERENCE.md) | Kontrak endpoint, format response, role, pengecualian binary |
| [`SCHEMA_CHANGES.md`](./SCHEMA_CHANGES.md) | Alasan skema, enum status, audit/GLP |
| [`.assets/http/`](./.assets/http/) | Request Kulala/HTTP Client siap pakai (login per role, CRUD, laporan) |

Alur kerja Kulala: pilih env `dev` → `auth.http` (login) → file domain lain. Lihat [`.assets/http/README.md`](./.assets/http/README.md).

---

## Perintah berguna

| Tugas | Perintah |
|-------|----------|
| Setup awal | `composer run setup` |
| Dev (serve + queue + pail + vite) | `composer run dev` |
| Tes | `composer run test` atau `php artisan test` |
| Tes filter | `php artisan test --filter=ReportGenerationTest` |
| Migrate + seed ulang | `php artisan migrate:fresh --seed` |
| Format PHP (Pint) | `./vendor/bin/pint` |
| Build aset | `npm run build` |

---

## Arsitektur kode (singkat)

```
routes/api.php                 # semua route bisnis (prefix /api)
app/Http/Controllers/Api/      # controller domain
app/Http/Resources/            # envelope { "data": ... }
app/Models/                    # Eloquent + state machine (borrowing, disposal)
app/Policies/                  # otorisasi per role
app/Observers/                 # stock ledger, audit trail, notifikasi, maintenance otomatis
app/Exports/ + resources/views/reports/   # Excel & PDF
tests/Feature/{Auth,Borrowing,Stock,Asset,Notification,Report,...}/
```

Konvensi penting:

- Status domain berbahasa **Indonesia** (`disetujui`, `rusak_berat`, …) — assert literal di test.
- Transisi status lewat endpoint khusus (`PATCH .../approve`), bukan ubah `status` di `update`.
- `stock_movements` immutable untuk quantity; stok item di-update lewat observer.
- Response JSON standar Resource; unduhan laporan/attachment = binary (lihat pengecualian di API reference).

---

## Stack terkait

- Laravel Sanctum — token API
- barryvdh/laravel-dompdf — laporan PDF
- maatwebsite/excel — laporan Excel

---

## Lisensi

Proyek ini memakai kerangka Laravel (MIT). Sesuaikan lisensi aplikasi sesuai kebijakan organisasi/kampus tempat magang.

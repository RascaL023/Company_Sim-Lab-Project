# SimLabProject - Laravel 12 Application

Hardware lab inventory/borrowing management (SIMLab). API-only backend; the web routes are just the default welcome page.

## Quick Commands

| Task | Command |
|------|---------|
| Initial setup | `composer run setup` (installs, copies `.env`, key:generate, migrate, npm install+build) |
| Start dev servers (all) | `composer run dev` (serve + queue + pail + vite via concurrently) |
| Run tests | `composer run test` (runs `config:clear` first) |
| Run single test | `./vendor/bin/phpunit --filter BorrowingApprovalTest` or `php artisan test --filter` |
| Build frontend | `npm run build` |
| Format code (Pint) | `./vendor/bin/pint` (default Laravel preset, no pint.json) |

## Architecture

- **All domain logic is in the API**: routes in `routes/api.php` (auto-prefixed `/api`), controllers in `app/Http/Controllers/Api/`. Do NOT add business routes to `routes/web.php`.
- **Catalog vs physical split (critical)**: `items` = catalog/master data (alat/bahan); `item_units` = physical units (serial, condition, location). Calibrations, maintenances, and borrowings reference `item_units`, NOT `items`.
- Validation is inline `$request->validate()` in controllers; Form Request classes exist only for Item (`app/Http/Requests/StoreItemRequest`, `UpdateItemRequest`). Responses use API Resources in `app/Http/Resources/`.
- `SCHEMA_CHANGES.md` is the authoritative design doc (schema rationale, status enums, audit/GLP requirements). Keep it in sync with schema changes.

## Domain Conventions (non-English enums)

- **Statuses are Indonesian**: borrowing `diajukan/disetujui/ditolak/diproses/selesai/batal`; usage `dicatat/diverifikasi/ditolak`; conditions `baik/rusak_ringan/rusak_berat/hilang`; roles `admin/staf`.
- Status-transition actions are `PATCH` endpoints (`borrowing-requests/{id}/approve|reject`, `usages/{id}/verify|reject`), not POST.
- `destroy` on borrowing requests/items sets `status = 'batal'` (soft-cancel) rather than deleting.
- **No Laravel Sanctum installed**: `auth:sanctum` on the `/user` route is non-functional; tests don't authenticate. Don't add auth middleware without adding the package.

## Database & Seeding

- SQLite: dev uses `database/database.sqlite`; tests use `:memory:` (set in `phpunit.xml`). Queue/cache/session use `database` driver locally, `array`/`sync` in tests.
- Seeders must be registered in `DatabaseSeeder` manually and ordered: users/categories/items BEFORE lifecycle seeders (`BorrowingLifecycleSeeder`, `UsageLifecycleSeeder`) which depend on them.

## Testing

- Feature tests live in domain subdirectories: `tests/Feature/{Borrowing,Stock,Audit,Usage,Maintenance}/`.
- Factories expose domain states: `User::factory()->admin()`, `Item::factory()->alat()`, `ItemUnit::factory()->baik()`.
- Tests use `RefreshDatabase`; request/response statuses and enum strings are asserted literally (Indonesian).

# Plan: Eloquent Models (Entity Layer) for SIMLab

## Context
- Migrations are complete and verified for MySQL + SQLite
- 8 tables need corresponding Eloquent models
- User model exists but needs updates for new columns
- 7 new models need to be created

## Models to Create/Update

### 1. User (Update Existing)
- Add fillable: `role`, `phone`, `is_active`
- Add casts: `role` (enum), `is_active` (boolean), `email_verified_at` (datetime)
- Add hidden: `password`, `remember_token`
- Relationships:
  - `createdItems()` - hasMany(Item::class, 'created_by')
  - `recordedCalibrations()` - hasMany(ItemCalibration::class, 'recorded_by')
  - `recordedMaintenances()` - hasMany(ItemMaintenance::class, 'recorded_by')
  - `borrowings()` - hasMany(Borrowing::class, 'borrower_id')
  - `approvedBorrowings()` - hasMany(Borrowing::class, 'approved_by')
  - `usages()` - hasMany(Usage::class, 'user_id')
  - `auditTrails()` - hasMany(AuditTrail::class, 'user_id')
- Scopes: `active()`, `admins()`, `staff()`
- Accessor: `isAdmin()`, `isStaff()`

### 2. Category (New)
- Fillable: `name`, `type`, `description`
- Casts: `type` (enum)
- Relationships:
  - `items()` - hasMany(Item::class)
- Scopes: `alat()`, `bahan()`

### 3. Item (New)
- Fillable: all columns except `id`, `created_at`, `updated_at`, `deleted_at`
- Casts:
  - `type` (enum), `condition_status` (enum)
  - `stock_quantity` (decimal:2), `minimum_stock` (decimal:2)
  - `purchase_date`, `expiry_date`, `next_calibration_date` (date)
  - `created_by` (integer)
- Relationships:
  - `category()` - belongsTo(Category::class)
  - `creator()` - belongsTo(User::class, 'created_by')
  - `calibrations()` - hasMany(ItemCalibration::class)
  - `maintenances()` - hasMany(ItemMaintenance::class)
  - `borrowings()` - hasMany(Borrowing::class)
  - `usages()` - hasMany(Usage::class)
  - `auditTrails()` - morphMany(AuditTrail::class, 'auditable')
- Scopes: `alat()`, `bahan()`, `baik()`, `rusak()`, `maintenance()`, `kadaluarsa()`, `lowStock()`, `needsCalibration()`, `expired()`
- Accessors: `isAlat()`, `isBahan()`, `isLowStock()`, `needsCalibration()`, `isExpired()`

### 4. ItemCalibration (New)
- Fillable: all columns except `id`, `created_at`, `updated_at`
- Casts:
  - `result` (enum), `calibration_date`, `next_calibration_date` (date)
  - `recorded_by` (integer)
- Relationships:
  - `item()` - belongsTo(Item::class)
  - `recorder()` - belongsTo(User::class, 'recorded_by')
- Scopes: `lulus()`, `tidakLulus()`, `upcoming()`, `overdue()`

### 5. ItemMaintenance (New)
- Fillable: all columns except `id`, `created_at`, `updated_at`
- Casts:
  - `status` (enum), `maintenance_date` (date)
  - `cost` (decimal:2), `recorded_by` (integer)
- Relationships:
  - `item()` - belongsTo(Item::class)
  - `recorder()` - belongsTo(User::class, 'recorded_by')
- Scopes: `selesai()`, `proses()`, `tertunda()`, `thisMonth()`

### 6. Borrowing (New)
- Fillable: all columns except `id`, `created_at`, `updated_at`
- Casts:
  - `status` (enum), `quantity` (decimal:2)
  - `borrow_date`, `expected_return_date`, `actual_return_date` (datetime)
  - `borrower_id`, `approved_by`, `item_id` (integer)
- Relationships:
  - `item()` - belongsTo(Item::class)
  - `borrower()` - belongsTo(User::class, 'borrower_id')
  - `approver()` - belongsTo(User::class, 'approved_by')
- Scopes: `dipinjam()`, `dikembalikan()`, `terlambat()`, `overdue()`, `thisMonth()`
- Accessors: `isOverdue()`, `isReturned()`

### 7. Usage (New)
- Fillable: all columns except `id`, `created_at`, `updated_at`
- Casts:
  - `quantity_used` (decimal:2)
  - `usage_date` (datetime)
  - `item_id`, `user_id` (integer)
- Relationships:
  - `item()` - belongsTo(Item::class)
  - `user()` - belongsTo(User::class)
- Scopes: `thisMonth()`, `byItem()`, `byUser()`

### 8. AuditTrail (New)
- Fillable: all columns except `id`, `created_at`, `updated_at`
- Casts:
  - `action` (enum)
  - `old_values`, `new_values` (array/object)
  - `auditable_id` (integer), `user_id` (integer)
- Relationships:
  - `user()` - belongsTo(User::class)
  - `auditable()` - morphTo()
- Scopes: `recent()`, `byAction()`, `byAuditable()`, `byUser()`

## Implementation Order
1. Category (no dependencies)
2. Item (depends on Category, User)
3. ItemCalibration (depends on Item, User)
4. ItemMaintenance (depends on Item, User)
5. Borrowing (depends on Item, User)
6. Usage (depends on Item, User)
7. AuditTrail (depends on User, polymorphic)
8. Update User (add relationships to all above)

## Validation
- Run `php artisan test` after each model
- Verify relationships work in tinker
- Check factory generation for each model

## Open Questions
1. Should we use `HasUuids` trait? (Currently using bigIncrements)
2. Any custom validation rules needed in models?
3. Should `AuditTrail` use a trait for automatic logging?
4. Do we need `SoftDeletes` on any other models besides Item?
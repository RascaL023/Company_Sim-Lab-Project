# API Testing Files for kulala.nvim

## Directory Structure
```
.assets/http/
├── items.http              (fully functional)
├── categories.http         (routes not yet wired)
├── borrowing-requests.http (routes not yet wired)
├── stock-movements.http    (routes not yet wired)
├── usages.http             (routes not yet wired)
├── calibration-maintenance.http (routes not yet wired)
├── audit-trails.http       (routes not yet wired)
└── attachments.http        (routes not yet wired)
```

## Current Status

| File | Routes Wired | Notes |
|------|-------------|-------|
| `items.http` | ✅ Yes | All CRUD operations enabled |
| Others | ❌ No | Routes exist in code but not registered in `routes/api.php` |

## Instructions for kulala.nvim

1. Open any `.http` file in nvim
2. Use `<leader>rt` (or `:Rest open`) to execute all requests
3. Use `<CR>` on a specific request line to execute it
4. Variables (`@baseUrl`) are defined at top of each file

## To Enable Full Testing

The following routes need to be registered in `routes/api.php`:

```php
Route::apiResource('categories', CategoryController::class);
Route::apiResource('borrowing-requests', BorrowingRequestController::class);
Route::apiResource('borrowing-items', BorrowingItemController::class);
Route::apiResource('stock-movements', StockMovementController::class);
Route::apiResource('usages', UsageController::class);
Route::apiResource('calibrations', ItemCalibrationController::class);
Route::apiResource('maintenances', ItemMaintenanceController::class);
Route::apiResource('audit-trails', AuditTrailController::class);
Route::apiResource('attachments', AttachmentController::class);
Route::apiResource('item-units', ItemUnitController::class);

// Additional endpoints
Route::get('items/{item}/stock-movements', [ItemController::class, 'stockMovements']);
Route::get('items/{item}/usages', [ItemController::class, 'usages']);
Route::get('items/{item}/calibrations', [ItemController::class, 'calibrations']);
Route::get('items/{item}/maintenances', [ItemController::class, 'maintenances']);
```

## Environment

- Laravel 12
- PHP 8.2+
- Database: SQLite (for testing)
- Auth: Sanctum (passport token for user endpoint)
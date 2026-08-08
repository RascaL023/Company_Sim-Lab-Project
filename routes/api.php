<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuditTrailController;
use App\Http\Controllers\Api\BorrowingItemController;
use App\Http\Controllers\Api\BorrowingRequestController;
use App\Http\Controllers\Api\CalibrationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemUnitController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\UsageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Item API endpoints
    Route::apiResource('items', ItemController::class);

    // Additional item endpoints
    Route::get('items/{item}/stock-movements', [ItemController::class, 'stockMovements']);
    Route::get('items/{item}/usages', [ItemController::class, 'usages']);
    Route::get('items/{item}/calibrations', [ItemController::class, 'calibrations']);
    Route::get('items/{item}/maintenances', [ItemController::class, 'maintenances']);
    Route::get('items/{item}/audit-trails', [ItemController::class, 'auditTrails']);

    // Category API
    Route::apiResource('categories', CategoryController::class);

    // Borrowing API
    Route::apiResource('borrowing-requests', BorrowingRequestController::class);
    Route::patch('borrowing-requests/{borrowingRequest}/approve', [BorrowingRequestController::class, 'approve']);
    Route::patch('borrowing-requests/{borrowingRequest}/reject', [BorrowingRequestController::class, 'reject']);
    Route::patch('borrowing-requests/{borrowingRequest}/cancel', [BorrowingRequestController::class, 'cancel']);
    Route::patch('borrowing-items/{borrowingItem}/checkout', [BorrowingItemController::class, 'checkout']);
    Route::patch('borrowing-items/{borrowingItem}/return', [BorrowingItemController::class, 'returnItem']);

    // Stock Movements API
    Route::apiResource('stock-movements', StockMovementController::class);

    // Usages API
    Route::apiResource('usages', UsageController::class);
    Route::patch('usages/{usage}/verify', [UsageController::class, 'verify']);
    Route::patch('usages/{usage}/reject', [UsageController::class, 'reject']);

    // Item Units API
    Route::apiResource('item-units', ItemUnitController::class);
    Route::get('items/{item}/units', [ItemController::class, 'units']);

    // Calibrations API
    Route::apiResource('calibrations', CalibrationController::class);

    // Maintenance API
    Route::apiResource('maintenances', MaintenanceController::class);

    // Audit Trails API
    Route::apiResource('audit-trails', AuditTrailController::class);
    Route::get('audit-trails/recent', [AuditTrailController::class, 'recent']);

    // Attachments API
    Route::apiResource('attachments', AttachmentController::class);
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
});

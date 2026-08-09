<?php

use App\Http\Controllers\Api\AssetDisposalController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuditTrailController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BorrowingItemController;
use App\Http\Controllers\Api\BorrowingRequestController;
use App\Http\Controllers\Api\CalibrationController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemUnitController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\StockOpnameController;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\UserController;
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

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // User management (admin only)
    Route::apiResource('users', UserController::class);

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
    Route::post('stock-opname', [StockOpnameController::class, 'store']);

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
    Route::get('audit-trails/recent', [AuditTrailController::class, 'recent']);
    Route::apiResource('audit-trails', AuditTrailController::class)->only(['index', 'show']);

    // Asset disposals (write-off approval)
    Route::post('asset-disposals', [AssetDisposalController::class, 'store']);
    Route::patch('asset-disposals/{assetDisposal}/approve', [AssetDisposalController::class, 'approve']);
    Route::patch('asset-disposals/{assetDisposal}/reject', [AssetDisposalController::class, 'reject']);

    // In-app notifications
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Attachments API
    Route::apiResource('attachments', AttachmentController::class);
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);
});

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Item API endpoints
Route::apiResource('items', ItemController::class)
    ->only(['index', 'store', 'show', 'update', 'destroy']);

// Optional: Add specific routes for filtering/searching if needed
// Route::get('items/low-stock', [ItemController::class, 'lowStock']);
// Route::get('items/needs-calibration', [ItemController::class, 'needsCalibration']);
// Route::get('items/expired', [ItemController::class, 'expired']);
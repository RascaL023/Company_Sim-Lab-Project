<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login')->name('login');
Route::view('/login', 'auth.login');
Route::view('/dashboard', 'dashboard')->name('dashboard');

// Katalog
Route::view('/items', 'items.index');
Route::redirect('/items/create', '/items');
// Route::view() is fine in current Laravel for simple params, but we pass IDs
// explicitly so Blade always receives the raw route segment for Alpine SPA pages.
Route::get('/items/{item}', function (string $item) {
    return view('items.show', ['item' => $item]);
});
Route::view('/categories', 'categories.index');
Route::view('/locations', 'locations.index');
Route::view('/item-units', 'item-units.index');

// Transaksi
Route::view('/borrowings', 'borrowings.index');
Route::view('/borrowings/create', 'borrowings.create');
Route::get('/borrowings/{borrowingRequest}', function (string $borrowingRequest) {
    return view('borrowings.show', ['borrowingRequest' => $borrowingRequest]);
});
Route::view('/usages', 'usages.index');
Route::view('/stock-opname', 'stock-opname.index');
Route::view('/disposals', 'disposals.index');

// Operasional
Route::view('/stock-movements', 'stock-movements.index');
Route::view('/calibrations', 'calibrations.index');
Route::view('/maintenances', 'maintenances.index');
Route::view('/attachments', 'attachments.index');
Route::view('/audit-trails', 'audit-trails.index');

// Administrasi
Route::view('/users', 'users.index');
Route::view('/reports', 'reports.index');
Route::view('/notifications', 'notifications.index');

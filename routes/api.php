<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KatalogBarangController;
use App\Http\Controllers\MitraController;
use App\Http\Controllers\PricelistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Authentication Routes (Public)
 */
Route::middleware('throttle:login')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/logo', [StoreSettingController::class, 'logo']);
});

Route::middleware('throttle:register')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

/**
 * Protected Routes (Require Authentication)
 */
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

    // Auth Routes
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    // User Routes
    Route::get('/user/all', [UserController::class, 'all']);
    Route::apiResource('user', UserController::class);
    Route::get('/user/all/paginated', [UserController::class, 'getAllPaginated']);

    Route::middleware('throttle:sensitive')->group(function () {
        Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
        Route::put('/user/{id}/update-password', [UserController::class, 'updatePassword']);
        Route::delete('/user/{id}', [UserController::class, 'destroy']);
    });

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);

    // Role & Permission Routes
    Route::get('/roles/capabilities', [\App\Http\Controllers\RoleController::class, 'capabilities'])->name('roles.capabilities');
    Route::apiResource('roles', \App\Http\Controllers\RoleController::class);
    Route::apiResource('permissions', \App\Http\Controllers\PermissionController::class);

    // Store Settings
    Route::get('/store-settings', [StoreSettingController::class, 'index']);
    Route::post('/store-settings', [StoreSettingController::class, 'update']);

    // ============================================
    // Master Data Routes
    // ============================================

    // Categories (Kategori)
    Route::get('/categories/all', [CategoryController::class, 'all']);
    Route::post('/categories/quick', [CategoryController::class, 'quickStore']);
    Route::apiResource('categories', CategoryController::class);

    // Units (Satuan)
    Route::get('/units/all', [UnitController::class, 'all']);
    Route::post('/units/quick', [UnitController::class, 'quickStore']);
    Route::apiResource('units', UnitController::class);

    // Suppliers (Supplier)
    Route::get('/suppliers/all', [SupplierController::class, 'all']);
    Route::post('/suppliers/quick', [SupplierController::class, 'quickStore']);
    Route::apiResource('suppliers', SupplierController::class);

    // Mitra (Mitra)
    Route::get('/mitra/all', [MitraController::class, 'all']);
    Route::post('/mitra/quick', [MitraController::class, 'quickStore']);
    Route::apiResource('mitra', MitraController::class);

    // Katalog Barang (Product Catalog)
    Route::get('/katalog-barang/all', [KatalogBarangController::class, 'all']);
    Route::apiResource('katalog-barang', KatalogBarangController::class);

    // ============================================
    // Purchase Routes (Pembelian)
    // ============================================
    Route::get('/purchases/generate-invoice', [PurchaseController::class, 'generateInvoiceNumber']);
    Route::post('/purchases/{id}/items', [PurchaseController::class, 'addItem']);
    Route::put('/purchases/{purchaseId}/items/{itemId}', [PurchaseController::class, 'updateItem']);
    Route::delete('/purchases/{purchaseId}/items/{itemId}', [PurchaseController::class, 'removeItem']);
    Route::apiResource('purchases', PurchaseController::class);

    // ============================================
    // Stock Routes (Stok Barang)
    // ============================================
    Route::get('/stock/summary', [StockController::class, 'summary']);
    Route::get('/stock/mutasi', [StockController::class, 'mutasi']);
    Route::get('/stock/expiring', [StockController::class, 'expiring']);
    Route::get('/stock/adjustments', [\App\Http\Controllers\StockAdjustmentController::class, 'index']);
    Route::post('/stock/adjustments', [\App\Http\Controllers\StockAdjustmentController::class, 'store']);

    // ============================================
    // Pricelist Routes
    // ============================================

    // Pricelist Umum
    Route::get('/pricelist/umum', [PricelistController::class, 'indexUmum']);
    Route::post('/pricelist/umum/open', [PricelistController::class, 'getOrCreateUmum']);
    Route::post('/pricelist/umum/{id}/items', [PricelistController::class, 'saveUmumItems']);
    Route::delete('/pricelist/umum/{id}', [PricelistController::class, 'destroyUmum']);

    // Pricelist Mitra
    Route::get('/pricelist/mitra', [PricelistController::class, 'indexMitra']);
    Route::post('/pricelist/mitra/open', [PricelistController::class, 'getOrCreateMitra']);
    Route::post('/pricelist/mitra/{id}/items', [PricelistController::class, 'saveMitraItems']);
    Route::delete('/pricelist/mitra/{id}', [PricelistController::class, 'destroyMitra']);

    // ============================================
    // Report Routes
    // ============================================
    Route::get('/reports/purchases', [ReportController::class, 'purchases']);
    Route::get('/reports/sales', [ReportController::class, 'sales']);
});

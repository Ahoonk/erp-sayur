<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('products', ProductController::class)->except(['show']);
    Route::patch('products/{product}/selling-price', [ProductController::class, 'updateSellingPrice'])
        ->name('products.update-selling-price');

    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/stock-in', [InventoryController::class, 'stockInPage'])->name('inventory.stock-in.page');
    Route::post('inventory/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');

    Route::get('inventory/stock-out', [InventoryController::class, 'stockOutPage'])->name('inventory.stock-out.page');
    Route::post('inventory/stock-out', [InventoryController::class, 'stockOut'])->name('inventory.stock-out');

    Route::get('inventory/reject', [InventoryController::class, 'stockRejectPage'])->name('inventory.reject.page');
    Route::post('inventory/reject', [InventoryController::class, 'stockReject'])->name('inventory.reject');

    Route::get('inventory/shrinkage', [InventoryController::class, 'stockShrinkagePage'])->name('inventory.shrinkage.page');
    Route::post('inventory/shrinkage', [InventoryController::class, 'stockShrinkage'])->name('inventory.shrinkage');

    Route::get('inventory/expired', [InventoryController::class, 'stockExpiredPage'])->name('inventory.expired.page');
    Route::post('inventory/expired', [InventoryController::class, 'stockExpired'])->name('inventory.expired');

    Route::post('inventory/waste', [InventoryController::class, 'stockWaste'])->name('inventory.waste');

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('stock-aging', [ReportController::class, 'stockAging'])->name('stock-aging');
        Route::get('real-margin', [ReportController::class, 'realMargin'])->name('real-margin');
        Route::get('waste', [ReportController::class, 'waste'])->name('waste');
        Route::get('ledger', [ReportController::class, 'ledger'])->name('ledger');
    });

    Route::resource('product-categories', ProductCategoryController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('units', UnitController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/auth.php';

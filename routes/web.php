<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\TransactionCancellationController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports/{type}/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

Route::view('/owner', 'owner.index')
    ->middleware(['auth', 'role:owner'])
    ->name('owner.index');

Route::middleware('auth')->group(function () {
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/inventory-movements', [InventoryMovementController::class, 'index'])->name('inventory-movements.index');
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/create/{draftKey?}', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases/review', [PurchaseController::class, 'prepare'])->name('purchases.prepare');
    Route::get('/purchases/drafts/{draftKey}/review', [PurchaseController::class, 'review'])->name('purchases.review');
    Route::post('/purchases/drafts/{draftKey}/confirm', [PurchaseController::class, 'confirm'])->name('purchases.confirm');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/create/{draftKey?}', [SaleController::class, 'create'])->name('sales.create');
    Route::post('/sales/review', [SaleController::class, 'prepare'])->name('sales.prepare');
    Route::get('/sales/drafts/{draftKey}/review', [SaleController::class, 'review'])->name('sales.review');
    Route::post('/sales/drafts/{draftKey}/confirm', [SaleController::class, 'confirm'])->name('sales.confirm');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

    Route::middleware('role:owner')->group(function () {
        Route::post('/transactions/{type}/{transaction}/cancel', [TransactionCancellationController::class, 'store'])->name('transactions.cancel');
        Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::patch('/products/{product}/archive', [ProductController::class, 'archive'])->name('products.archive');

        Route::resource('suppliers', SupplierController::class)->except('destroy');
        Route::resource('customers', CustomerController::class)->except('destroy');
        Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('stock-adjustments.create');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('stock-adjustments.store');
    });

    Route::get('/stock-adjustments/{stockAdjustment}', [StockAdjustmentController::class, 'show'])->name('stock-adjustments.show');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
});

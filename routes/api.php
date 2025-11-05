<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupplierController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {
    // Parts Management
    Route::get('/parts', [PartController::class, 'index']);
    Route::post('/parts', [PartController::class, 'store']);
    Route::put('/parts/{id}', [PartController::class, 'update']);
    Route::delete('/parts/{id}', [PartController::class, 'destroy']);

    // Suppliers Management
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

    // Orders Management
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);

    // Dashboard & Reports
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/reports/sales', [ReportsController::class, 'sales']);
    Route::get('/reports/top-selling', [ReportsController::class, 'topSelling']);
    Route::get('/reports/supplier-performance', [ReportsController::class, 'supplierPerformance']);
    Route::get('/reports/inventory-analysis', [ReportsController::class, 'inventoryAnalysis']);
    Route::get('/reports/customer-insights', [ReportsController::class, 'customerInsights']);
});
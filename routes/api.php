<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TankController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\StockLevelController;
use App\Http\Controllers\DailySaleController;
use App\Http\Controllers\BulkSaleController;
use App\Http\Controllers\RetailSaleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PriceAdjustmentController;
use App\Http\Controllers\InventoryReconciliationController;
use App\Http\Controllers\TankDippingController;
use App\Http\Controllers\TankTransferController;
use App\Http\Controllers\TankGroupController;
use App\Http\Controllers\NozzleController;
use App\Http\Controllers\AssetCategoryController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGroupController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\LoginLogController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\LevelController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\PayslipController;
use App\Http\Controllers\SalaryPaymentController;
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
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
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

    // Filling Station - Products
    Route::prefix('filling')->group(function () {
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::get('/products/inventory/summary', [ProductController::class, 'inventory']);

        // Filling Station - Tanks
        Route::get('/tanks', [TankController::class, 'index']);
        Route::get('/tanks/{id}', [TankController::class, 'show']);
        Route::post('/tanks', [TankController::class, 'store']);
        Route::put('/tanks/{id}', [TankController::class, 'update']);
        Route::delete('/tanks/{id}', [TankController::class, 'destroy']);

        // Filling Station - Customers
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/credit-limit/report', [CustomerController::class, 'creditLimitReport']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
        Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

        // Filling Station - Shifts
        Route::get('/shifts', [ShiftController::class, 'index']);
        Route::get('/shifts/{id}', [ShiftController::class, 'show']);
        Route::post('/shifts', [ShiftController::class, 'store']);
        Route::put('/shifts/{id}', [ShiftController::class, 'update']);
        Route::delete('/shifts/{id}', [ShiftController::class, 'destroy']);
        Route::post('/shifts/{id}/close', [ShiftController::class, 'close']);
        Route::post('/shifts/{id}/approve', [ShiftController::class, 'approve']);
        Route::post('/shifts/{id}/save-values', [ShiftController::class, 'saveValues']);
        Route::delete('/shifts/{id}/delete-values', [ShiftController::class, 'deleteValues']);

        // Filling Station - Stock Levels
        Route::get('/stock-levels', [StockLevelController::class, 'index']);
        Route::post('/stock-levels', [StockLevelController::class, 'store']);
        Route::put('/stock-levels/{id}', [StockLevelController::class, 'update']);
        Route::delete('/stock-levels/{id}', [StockLevelController::class, 'destroy']);

        // Filling Station - Daily Sales
        Route::get('/daily-sales', [DailySaleController::class, 'index']);
        Route::post('/daily-sales', [DailySaleController::class, 'store']);
        Route::put('/daily-sales/{id}', [DailySaleController::class, 'update']);
        Route::delete('/daily-sales/{id}', [DailySaleController::class, 'destroy']);

        // Filling Station - Bulk Sales
        Route::get('/bulk-sales', [BulkSaleController::class, 'index']);
        Route::get('/bulk-sales/{id}', [BulkSaleController::class, 'show']);
        Route::post('/bulk-sales', [BulkSaleController::class, 'store']);
        Route::put('/bulk-sales/{id}', [BulkSaleController::class, 'update']);
        Route::delete('/bulk-sales/{id}', [BulkSaleController::class, 'destroy']);

        // Filling Station - Retail Sales
        Route::get('/retail-sales', [RetailSaleController::class, 'index']);
        Route::get('/retail-sales/{id}', [RetailSaleController::class, 'show']);
        Route::post('/retail-sales', [RetailSaleController::class, 'store']);
        Route::put('/retail-sales/{id}', [RetailSaleController::class, 'update']);
        Route::delete('/retail-sales/{id}', [RetailSaleController::class, 'destroy']);

        // Filling Station - Purchases
        Route::get('/purchases', [PurchaseController::class, 'index']);
        Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
        Route::post('/purchases', [PurchaseController::class, 'store']);
        Route::put('/purchases/{id}', [PurchaseController::class, 'update']);
        Route::delete('/purchases/{id}', [PurchaseController::class, 'destroy']);
        Route::post('/purchases/{id}/receive', [PurchaseController::class, 'receive']);
        Route::post('/purchases/{id}/return-reception', [PurchaseController::class, 'returnReception']);

        // Filling Station - Price Adjustments
        Route::get('/price-adjustments', [PriceAdjustmentController::class, 'index']);
        Route::post('/price-adjustments', [PriceAdjustmentController::class, 'store']);

        // Filling Station - Inventory Reconciliations
        Route::get('/inventory-reconciliations', [InventoryReconciliationController::class, 'index']);
        Route::post('/inventory-reconciliations', [InventoryReconciliationController::class, 'store']);

        // Filling Station - Tank Dippings
        Route::get('/tank-dippings', [TankDippingController::class, 'index']);
        Route::get('/tank-dippings/variance/report', [TankDippingController::class, 'varianceReport']);
        Route::get('/tank-dippings/{id}', [TankDippingController::class, 'show']);
        Route::post('/tank-dippings', [TankDippingController::class, 'store']);
        Route::put('/tank-dippings/{id}', [TankDippingController::class, 'update']);
        Route::delete('/tank-dippings/{id}', [TankDippingController::class, 'destroy']);

        // Filling Station - Tank Transfers
        Route::get('/tank-transfers', [TankTransferController::class, 'index']);
        Route::get('/tank-transfers/{id}', [TankTransferController::class, 'show']);
        Route::post('/tank-transfers', [TankTransferController::class, 'store']);
        Route::put('/tank-transfers/{id}', [TankTransferController::class, 'update']);
        Route::delete('/tank-transfers/{id}', [TankTransferController::class, 'destroy']);

        // Filling Station - Tank Groups
        Route::get('/tank-groups', [TankGroupController::class, 'index']);
        Route::get('/tank-groups/{id}', [TankGroupController::class, 'show']);
        Route::post('/tank-groups', [TankGroupController::class, 'store']);
        Route::put('/tank-groups/{id}', [TankGroupController::class, 'update']);
        Route::delete('/tank-groups/{id}', [TankGroupController::class, 'destroy']);

        // Filling Station - Nozzles
        Route::get('/nozzles', [NozzleController::class, 'index']);
        Route::get('/nozzles/{id}', [NozzleController::class, 'show']);
        Route::post('/nozzles', [NozzleController::class, 'store']);
        Route::put('/nozzles/{id}', [NozzleController::class, 'update']);
        Route::delete('/nozzles/{id}', [NozzleController::class, 'destroy']);

        // Filling Station - Asset Categories
        Route::get('/asset-categories', [AssetCategoryController::class, 'index']);
        Route::get('/asset-categories/{id}', [AssetCategoryController::class, 'show']);
        Route::post('/asset-categories', [AssetCategoryController::class, 'store']);
        Route::put('/asset-categories/{id}', [AssetCategoryController::class, 'update']);
        Route::delete('/asset-categories/{id}', [AssetCategoryController::class, 'destroy']);

        // Filling Station - Assets
        Route::get('/assets', [AssetController::class, 'index']);
        Route::get('/assets/{id}', [AssetController::class, 'show']);
        Route::post('/assets', [AssetController::class, 'store']);
        Route::put('/assets/{id}', [AssetController::class, 'update']);
        Route::delete('/assets/{id}', [AssetController::class, 'destroy']);
        Route::get('/assets/statistics/summary', [AssetController::class, 'statistics']);

        // Filling Station - Locations
        Route::get('/locations', [LocationController::class, 'index']);
        Route::get('/locations/{id}', [LocationController::class, 'show']);
        Route::post('/locations', [LocationController::class, 'store']);
        Route::put('/locations/{id}', [LocationController::class, 'update']);
        Route::delete('/locations/{id}', [LocationController::class, 'destroy']);

        // Filling Station - Users
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus']);

        // Filling Station - User Groups
        Route::get('/user-groups', [UserGroupController::class, 'index']);
        Route::get('/user-groups/{userGroup}', [UserGroupController::class, 'show']);
        Route::post('/user-groups', [UserGroupController::class, 'store']);
        Route::put('/user-groups/{userGroup}', [UserGroupController::class, 'update']);
        Route::delete('/user-groups/{userGroup}', [UserGroupController::class, 'destroy']);

        // Filling Station - Activity Logs
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);

        // Filling Station - Login Logs
        Route::get('/login-logs', [LoginLogController::class, 'index']);

        // Filling Station - Settings
        Route::get('/settings', [SettingController::class, 'index']);
        Route::get('/settings/{group}', [SettingController::class, 'getByGroup']);
        Route::post('/settings/general', [SettingController::class, 'updateGeneral']);
        Route::post('/settings/hr', [SettingController::class, 'updateHr']);
        Route::post('/settings/options', [SettingController::class, 'updateOptions']);
        Route::get('/settings/value/{key}', [SettingController::class, 'getSetting']);
        Route::post('/settings/value', [SettingController::class, 'setSetting']);

        // Filling Station - Accounts
        Route::get('/accounts', [AccountController::class, 'index']);
        Route::get('/accounts/{account}', [AccountController::class, 'show']);
        Route::post('/accounts', [AccountController::class, 'store']);
        Route::put('/accounts/{account}', [AccountController::class, 'update']);
        Route::delete('/accounts/{account}', [AccountController::class, 'destroy']);
        Route::get('/accounts/type/{type}', [AccountController::class, 'getByType']);

        // Filling Station - Vouchers
        Route::get('/vouchers', [VoucherController::class, 'index']);
        Route::get('/vouchers/{voucher}', [VoucherController::class, 'show']);
        Route::post('/vouchers', [VoucherController::class, 'store']);
        Route::put('/vouchers/{voucher}', [VoucherController::class, 'update']);
        Route::delete('/vouchers/{voucher}', [VoucherController::class, 'destroy']);
        Route::patch('/vouchers/{voucher}/approve', [VoucherController::class, 'approve']);
        Route::patch('/vouchers/{voucher}/submit', [VoucherController::class, 'submit']);

        // Filling Station - Journal Entries
        Route::get('/journal-entries', [JournalEntryController::class, 'index']);
        Route::get('/journal-entries/pending', [JournalEntryController::class, 'pending']);
        Route::get('/journal-entries/{journalEntry}', [JournalEntryController::class, 'show']);
        Route::post('/journal-entries', [JournalEntryController::class, 'store']);
        Route::post('/journal-entries/approve-multiple', [JournalEntryController::class, 'approveMultiple']);
        Route::patch('/journal-entries/{journalEntry}/post', [JournalEntryController::class, 'post']);
        Route::post('/journal-entries/generate', [JournalEntryController::class, 'generate']);

        // Filling Station - Payments
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::put('/payments/{payment}', [PaymentController::class, 'update']);
        Route::delete('/payments/{payment}', [PaymentController::class, 'destroy']);

        // HR - Departments
        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::post('/departments', [DepartmentController::class, 'store']);
        Route::get('/departments/export', [DepartmentController::class, 'export']);
        Route::get('/departments/{id}', [DepartmentController::class, 'show'])->whereNumber('id');

        // HR - Levels
        Route::get('/levels', [LevelController::class, 'index']);
        Route::post('/levels', [LevelController::class, 'store']);
        Route::put('/levels/{id}', [LevelController::class, 'update']);
        Route::delete('/levels/{id}', [LevelController::class, 'destroy']);
        Route::get('/levels/export', [LevelController::class, 'export']);

        // HR - Staff
        Route::get('/staff', [StaffController::class, 'index']);
        Route::get('/staff/{id}', [StaffController::class, 'show']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::put('/staff/{id}', [StaffController::class, 'update']);
        Route::delete('/staff/{id}', [StaffController::class, 'destroy']);

        // Loans
        Route::get('/loans', [LoanController::class, 'index']);
        Route::post('/loans', [LoanController::class, 'store']);
        Route::put('/loans/{id}', [LoanController::class, 'update']);
        Route::delete('/loans/{id}', [LoanController::class, 'destroy']);
        Route::get('/loans/export', [LoanController::class, 'export']);
        Route::get('/loans/export-pdf', [LoanController::class, 'exportPdf']);

        // Attendance
        Route::get('/attendance/summary', [AttendanceController::class, 'summary']);
        Route::get('/attendance/summary/export', [AttendanceController::class, 'summaryExport']);
        Route::get('/attendance/summary/export-pdf', [AttendanceController::class, 'summaryExportPdf']);
        Route::post('/attendance', [AttendanceController::class, 'store']);
        Route::get('/attendance/holidays', [AttendanceController::class, 'holidaysList']);
        Route::post('/attendance/holidays', [AttendanceController::class, 'holidaysStore']);
        Route::delete('/attendance/holidays/{id}', [AttendanceController::class, 'holidaysDestroy']);

        // Vacations
        Route::get('/vacations', [VacationController::class, 'index']);
        Route::post('/vacations', [VacationController::class, 'store']);
        Route::put('/vacations/{id}', [VacationController::class, 'update']);
        Route::delete('/vacations/{id}', [VacationController::class, 'destroy']);
        Route::get('/vacations/export', [VacationController::class, 'export']);
        Route::get('/vacations/export-pdf', [VacationController::class, 'exportPdf']);

        // Payslips
        Route::post('/payslips/generate', [PayslipController::class, 'generate']);
        Route::get('/payslips', [PayslipController::class, 'index']);
        Route::get('/payslips/export', [PayslipController::class, 'export']);
        Route::get('/payslips/export-pdf', [PayslipController::class, 'exportPdf']);

        // Salary Payments
        Route::post('/salary-payments', [SalaryPaymentController::class, 'store']);
        Route::get('/salary-payments', [SalaryPaymentController::class, 'index']);
        Route::get('/salary-payments/export', [SalaryPaymentController::class, 'export']);
        Route::get('/salary-payments/export-pdf', [SalaryPaymentController::class, 'exportPdf']);
    });
    Route::get('/reports/supplier-performance', [ReportsController::class, 'supplierPerformance']);
    Route::get('/reports/inventory-analysis', [ReportsController::class, 'inventoryAnalysis']);
    Route::get('/reports/customer-insights', [ReportsController::class, 'customerInsights']);
});
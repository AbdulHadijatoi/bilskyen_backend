<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EnquiryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialAccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\VersionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::get('/version.json', [VersionController::class, 'getVersion']);

// Featured vehicles (public)
Route::get('/vehicles/get-featured-vehicles', [VehicleController::class, 'getFeaturedVehicles']);

// Authentication routes (Better-Auth compatible)
Route::prefix('auth')->group(function () {
    // Public auth routes
    Route::post('/sign-up/email', [AuthController::class, 'signUp']);
    Route::post('/sign-in/email', [AuthController::class, 'signIn']);
    
    // Protected auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/sign-out', [AuthController::class, 'signOut']);
        Route::get('/get-session', [AuthController::class, 'getSession']);
        Route::post('/update-user', [AuthController::class, 'updateUser']);
        Route::post('/revoke-session', [AuthController::class, 'revokeSession']);
    });
    
    // TODO: Implement remaining endpoints
    Route::post('/sign-in/magic-link', function () {
        return response()->json(['message' => 'Magic link endpoint - to be implemented'], 501);
    });
    
    Route::get('/verify-magic-link', function () {
        return response()->json(['message' => 'Verify magic link endpoint - to be implemented'], 501);
    });
    
    Route::post('/forget-password', function () {
        return response()->json(['message' => 'Forgot password endpoint - to be implemented'], 501);
    });
    
    Route::post('/reset-password', function () {
        return response()->json(['message' => 'Reset password endpoint - to be implemented'], 501);
    });
    
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
    
    Route::get('/verify-email', function () {
        return response()->json(['message' => 'Verify email endpoint - to be implemented'], 501);
    });
    
    Route::post('/change-email', function () {
        return response()->json(['message' => 'Change email endpoint - to be implemented'], 501);
    })->middleware('auth:sanctum');
});

// Helper function to apply permission middleware with correct syntax
if (!function_exists('permission_middleware')) {
    function permission_middleware($permission, $action) {
        return 'permission:' . $permission . ',' . $action;
    }
}

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    
    // File upload routes
    Route::post('/file-upload', [FileUploadController::class, 'upload'])->middleware(permission_middleware('files', 'upload'));
    Route::delete('/file-upload', [FileUploadController::class, 'delete'])->middleware(permission_middleware('files', 'delete'));
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->middleware(permission_middleware('notification', 'list'));
    Route::get('/notifications/count', [NotificationController::class, 'getCount'])->middleware(permission_middleware('notification', 'list'));
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead'])->middleware(permission_middleware('notification', 'update'));
    Route::get('/notifications/dispatch', [NotificationController::class, 'dispatch'])->middleware(['cron.auth', permission_middleware('notification', 'list')]);
    Route::post('/actions/notifications/create', [NotificationController::class, 'create'])->middleware(permission_middleware('notification', 'create'));
    Route::post('/actions/notifications/update', [NotificationController::class, 'update'])->middleware(permission_middleware('notification', 'update'));
    Route::post('/actions/notifications/delete', [NotificationController::class, 'delete'])->middleware(permission_middleware('notification', 'delete'));
    
    // Push notification routes
    Route::post('/push-notifications/subscribe', [PushNotificationController::class, 'subscribe']);
    Route::post('/push-notifications/unsubscribe', [PushNotificationController::class, 'unsubscribe']);
    Route::post('/push-notifications/send', [PushNotificationController::class, 'send'])->middleware(permission_middleware('notification', 'create'));
    
    // Vehicle routes
    Route::get('/dealer/get-vehicles', [VehicleController::class, 'getVehicles'])->middleware(permission_middleware('vehicle', 'list'));
    Route::get('/dealer/get-vehicles-overview', [VehicleController::class, 'getVehiclesOverview'])->middleware(permission_middleware('dashboard', 'view'));
    Route::get('/dealer/get-vehicle-by-serial/{serialNo}', [VehicleController::class, 'getBySerial'])->middleware(permission_middleware('vehicle', 'view'));
    Route::post('/actions/vehicles/create', [VehicleController::class, 'store'])->middleware(permission_middleware('vehicle', 'create'));
    Route::post('/actions/vehicles/update', [VehicleController::class, 'update'])->middleware(permission_middleware('vehicle', 'update'));
    Route::post('/actions/vehicles/delete', [VehicleController::class, 'destroy'])->middleware(permission_middleware('vehicle', 'delete'));
    
    // Contact routes
    Route::get('/dealer/get-contacts', [ContactController::class, 'getContacts'])->middleware(permission_middleware('contact', 'list'));
    Route::get('/dealer/get-contact-by-serial/{serialNo}', [ContactController::class, 'getBySerial'])->middleware(permission_middleware('contact', 'view'));
    Route::post('/actions/contacts/create', [ContactController::class, 'store'])->middleware(permission_middleware('contact', 'create'));
    Route::post('/actions/contacts/update', [ContactController::class, 'update'])->middleware(permission_middleware('contact', 'update'));
    Route::post('/actions/contacts/delete', [ContactController::class, 'destroy'])->middleware(permission_middleware('contact', 'delete'));
    
    // Enquiry routes
    Route::get('/dealer/get-enquiries', [EnquiryController::class, 'getEnquiries'])->middleware(permission_middleware('enquiry', 'list'));
    Route::get('/dealer/get-enquiry-by-serial/{serialNo}', [EnquiryController::class, 'getBySerial'])->middleware(permission_middleware('enquiry', 'view'));
    Route::post('/actions/enquiries/create', [EnquiryController::class, 'create'])->middleware(permission_middleware('enquiry', 'create'));
    Route::post('/actions/enquiries/update', [EnquiryController::class, 'update'])->middleware(permission_middleware('enquiry', 'update'));
    Route::post('/actions/enquiries/delete', [EnquiryController::class, 'delete'])->middleware(permission_middleware('enquiry', 'delete'));
    
    // Purchase routes
    Route::get('/dealer/get-purchases', [PurchaseController::class, 'getPurchases'])->middleware(permission_middleware('purchase', 'list'));
    Route::get('/dealer/get-purchase-by-serial/{serialNo}', [PurchaseController::class, 'getBySerial'])->middleware(permission_middleware('purchase', 'view'));
    Route::post('/actions/purchases/create', [PurchaseController::class, 'create'])->middleware(permission_middleware('purchase', 'create'));
    Route::post('/actions/purchases/update', [PurchaseController::class, 'update'])->middleware(permission_middleware('purchase', 'update'));
    Route::post('/actions/purchases/delete', [PurchaseController::class, 'delete'])->middleware(permission_middleware('purchase', 'delete'));
    
    // Sale routes
    Route::get('/dealer/get-sales', [SaleController::class, 'getSales'])->middleware(permission_middleware('sale', 'list'));
    Route::get('/dealer/get-sales-overview', [SaleController::class, 'getSalesOverview'])->middleware(permission_middleware('dashboard', 'view'));
    Route::get('/dealer/get-sale-by-serial/{serialNo}', [SaleController::class, 'getBySerial'])->middleware(permission_middleware('sale', 'view'));
    Route::post('/actions/sales/create', [SaleController::class, 'create'])->middleware(permission_middleware('sale', 'create'));
    Route::post('/actions/sales/update', [SaleController::class, 'update'])->middleware(permission_middleware('sale', 'update'));
    Route::post('/actions/sales/delete', [SaleController::class, 'delete'])->middleware(permission_middleware('sale', 'delete'));
    
    // Expense routes
    Route::get('/dealer/get-expenses', [ExpenseController::class, 'getExpenses'])->middleware(permission_middleware('expense', 'list'));
    Route::get('/dealer/get-expense-by-serial/{serialNo}', [ExpenseController::class, 'getBySerial'])->middleware(permission_middleware('expense', 'view'));
    Route::post('/actions/expenses/create', [ExpenseController::class, 'create'])->middleware(permission_middleware('expense', 'create'));
    Route::post('/actions/expenses/update', [ExpenseController::class, 'update'])->middleware(permission_middleware('expense', 'update'));
    Route::post('/actions/expenses/delete', [ExpenseController::class, 'delete'])->middleware(permission_middleware('expense', 'delete'));
    
    // Financial Account routes
    Route::get('/dealer/accounting/get-financial-accounts', [FinancialAccountController::class, 'getFinancialAccounts'])->middleware(permission_middleware('financial-account', 'list'));
    Route::get('/dealer/accounting/get-financial-account-by-serial/{serialNo}', [FinancialAccountController::class, 'getBySerial'])->middleware(permission_middleware('financial-account', 'view'));
    Route::post('/actions/financial-accounts/create', [FinancialAccountController::class, 'create'])->middleware(permission_middleware('financial-account', 'create'));
    Route::post('/actions/financial-accounts/update', [FinancialAccountController::class, 'update'])->middleware(permission_middleware('financial-account', 'update'));
    Route::post('/actions/financial-accounts/delete', [FinancialAccountController::class, 'delete'])->middleware(permission_middleware('financial-account', 'delete'));
    
    // Transaction routes
    Route::get('/dealer/accounting/get-general-ledger-entries', [TransactionController::class, 'getGeneralLedgerEntries'])->middleware(permission_middleware('transaction', 'list'));
    Route::get('/dealer/accounting/get-transaction/{transactionId}', [TransactionController::class, 'getTransaction'])->middleware(permission_middleware('transaction', 'list'));
    Route::get('/dealer/accounting/get-transaction-by-serial/{serialNo}', [TransactionController::class, 'getBySerial'])->middleware(permission_middleware('transaction', 'view'));
    Route::post('/actions/transactions/create', [TransactionController::class, 'create'])->middleware(permission_middleware('transaction', 'create'));
    Route::post('/actions/transactions/update', [TransactionController::class, 'update'])->middleware(permission_middleware('transaction', 'update'));
    Route::post('/actions/transactions/delete', [TransactionController::class, 'delete'])->middleware(permission_middleware('transaction', 'delete'));
    
    // Financial Overview routes
    Route::get('/dealer/accounting/get-financial-overview', [AccountingController::class, 'getFinancialOverview'])->middleware(permission_middleware('dashboard', 'view'));
    Route::get('/dealer/accounting/get-financial-overview-chart', [AccountingController::class, 'getFinancialOverviewChart'])->middleware(permission_middleware('dashboard', 'view'));
    
    // Financial Report routes
    Route::get('/dealer/accounting/financial-reports/balance-sheet', [FinancialReportController::class, 'getBalanceSheet'])->middleware(permission_middleware('dashboard', 'view'));
    Route::get('/dealer/accounting/financial-reports/income-statement', [FinancialReportController::class, 'getIncomeStatement'])->middleware(permission_middleware('dashboard', 'view'));
    Route::get('/dealer/accounting/financial-reports/cash-flow-statement', [FinancialReportController::class, 'getCashFlowStatement'])->middleware(permission_middleware('dashboard', 'view'));
    
    // Account/Profile routes
    Route::post('/actions/account/update-profile', function () {
        // TODO: Implement update profile
        return response()->json(['message' => 'Update profile endpoint - to be implemented'], 501);
    });
    
    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/get-notifications', [AdminNotificationController::class, 'getNotifications'])->middleware(permission_middleware('notification', 'list'));
        Route::get('/get-users', [AdminUserController::class, 'getUsers'])->middleware(permission_middleware('user', 'list'));
    });
});

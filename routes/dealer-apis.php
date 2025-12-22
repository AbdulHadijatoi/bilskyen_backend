<?php

use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\FinancialReportController;

/*
|--------------------------------------------------------------------------
| Dealer API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for dealer functionality.
| These routes are loaded within the "api" middleware group.
|
*/

// Helper function to apply permission middleware with correct syntax
if (!function_exists('permission_middleware')) {
    function permission_middleware($permission, $action) {
        return 'permission:' . $permission . ',' . $action;
    }
}

// Dealer routes (requires authentication)
Route::middleware('jwt.auth')->group(function () {
    
    // File upload routes
    Route::post('/file-upload', [FileUploadController::class, 'upload'])
        ->middleware(permission_middleware('files', 'upload'));
    Route::delete('/file-upload', [FileUploadController::class, 'delete'])
        ->middleware(permission_middleware('files', 'delete'));
    
    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications'])
            ->middleware(permission_middleware('notification', 'list'));
        Route::get('/count', [NotificationController::class, 'getCount'])
            ->middleware(permission_middleware('notification', 'list'));
        Route::post('/mark-as-read', [NotificationController::class, 'markAsRead'])
            ->middleware(permission_middleware('notification', 'update'));
        Route::get('/dispatch', [NotificationController::class, 'dispatch'])
            ->middleware(['cron.auth', permission_middleware('notification', 'list')]);
    });
    
    // Push notification routes
    Route::prefix('push-notifications')->group(function () {
        Route::post('/subscribe', [PushNotificationController::class, 'subscribe']);
        Route::post('/unsubscribe', [PushNotificationController::class, 'unsubscribe']);
        Route::post('/send', [PushNotificationController::class, 'send'])
            ->middleware(permission_middleware('notification', 'create'));
    });
    
    // Dealer prefix group
    Route::prefix('dealer')->group(function () {
        
        // Vehicle routes
        Route::get('/get-vehicles', [VehicleController::class, 'getVehicles'])
            ->middleware(permission_middleware('vehicle', 'list'));
        Route::get('/get-vehicles-overview', [VehicleController::class, 'getVehiclesOverview'])
            ->middleware(permission_middleware('dashboard', 'view'));
        Route::get('/get-vehicle-by-serial/{serialNo}', [VehicleController::class, 'getBySerial'])
            ->middleware(permission_middleware('vehicle', 'view'));
        
        // Contact routes
        Route::get('/get-contacts', [ContactController::class, 'getContacts'])
            ->middleware(permission_middleware('contact', 'list'));
        Route::get('/get-contact-by-serial/{serialNo}', [ContactController::class, 'getBySerial'])
            ->middleware(permission_middleware('contact', 'view'));
        
        // Enquiry routes
        Route::get('/get-enquiries', [EnquiryController::class, 'getEnquiries'])
            ->middleware(permission_middleware('enquiry', 'list'));
        Route::get('/get-enquiry-by-serial/{serialNo}', [EnquiryController::class, 'getBySerial'])
            ->middleware(permission_middleware('enquiry', 'view'));
        
        // Purchase routes
        Route::get('/get-purchases', [PurchaseController::class, 'getPurchases'])
            ->middleware(permission_middleware('purchase', 'list'));
        Route::get('/get-purchase-by-serial/{serialNo}', [PurchaseController::class, 'getBySerial'])
            ->middleware(permission_middleware('purchase', 'view'));
        
        // Sale routes
        Route::get('/get-sales', [SaleController::class, 'getSales'])
            ->middleware(permission_middleware('sale', 'list'));
        Route::get('/get-sales-overview', [SaleController::class, 'getSalesOverview'])
            ->middleware(permission_middleware('dashboard', 'view'));
        Route::get('/get-sale-by-serial/{serialNo}', [SaleController::class, 'getBySerial'])
            ->middleware(permission_middleware('sale', 'view'));
        
        // Expense routes
        Route::get('/get-expenses', [ExpenseController::class, 'getExpenses'])
            ->middleware(permission_middleware('expense', 'list'));
        Route::get('/get-expense-by-serial/{serialNo}', [ExpenseController::class, 'getBySerial'])
            ->middleware(permission_middleware('expense', 'view'));
        
        // Accounting prefix group
        Route::prefix('accounting')->group(function () {
            
            // Financial Account routes
            Route::get('/get-financial-accounts', [FinancialAccountController::class, 'getFinancialAccounts'])
                ->middleware(permission_middleware('financial-account', 'list'));
            Route::get('/get-financial-account-by-serial/{serialNo}', [FinancialAccountController::class, 'getBySerial'])
                ->middleware(permission_middleware('financial-account', 'view'));
            
            // Transaction routes
            Route::get('/get-general-ledger-entries', [TransactionController::class, 'getGeneralLedgerEntries'])
                ->middleware(permission_middleware('transaction', 'list'));
            Route::get('/get-transaction/{transactionId}', [TransactionController::class, 'getTransaction'])
                ->middleware(permission_middleware('transaction', 'list'));
            Route::get('/get-transaction-by-serial/{serialNo}', [TransactionController::class, 'getBySerial'])
                ->middleware(permission_middleware('transaction', 'view'));
            
            // Financial Overview routes
            Route::get('/get-financial-overview', [AccountingController::class, 'getFinancialOverview'])
                ->middleware(permission_middleware('dashboard', 'view'));
            Route::get('/get-financial-overview-chart', [AccountingController::class, 'getFinancialOverviewChart'])
                ->middleware(permission_middleware('dashboard', 'view'));
            
            // Financial Reports prefix group
            Route::prefix('financial-reports')->group(function () {
                Route::get('/balance-sheet', [FinancialReportController::class, 'getBalanceSheet'])
                    ->middleware(permission_middleware('dashboard', 'view'));
                Route::get('/income-statement', [FinancialReportController::class, 'getIncomeStatement'])
                    ->middleware(permission_middleware('dashboard', 'view'));
                Route::get('/cash-flow-statement', [FinancialReportController::class, 'getCashFlowStatement'])
                    ->middleware(permission_middleware('dashboard', 'view'));
            });
        });
    });
    
    // Actions prefix group
    Route::prefix('actions')->group(function () {
        
        // Notification actions
        Route::prefix('notifications')->group(function () {
            Route::post('/create', [NotificationController::class, 'create'])
                ->middleware(permission_middleware('notification', 'create'));
            Route::post('/update', [NotificationController::class, 'update'])
                ->middleware(permission_middleware('notification', 'update'));
            Route::post('/delete', [NotificationController::class, 'delete'])
                ->middleware(permission_middleware('notification', 'delete'));
        });
        
        // Vehicle actions
        Route::prefix('vehicles')->group(function () {
            Route::post('/create', [VehicleController::class, 'store'])
                ->middleware(permission_middleware('vehicle', 'create'));
            Route::post('/update', [VehicleController::class, 'update'])
                ->middleware(permission_middleware('vehicle', 'update'));
            Route::post('/delete', [VehicleController::class, 'destroy'])
                ->middleware(permission_middleware('vehicle', 'delete'));
        });
        
        // Contact actions
        Route::prefix('contacts')->group(function () {
            Route::post('/create', [ContactController::class, 'store'])
                ->middleware(permission_middleware('contact', 'create'));
            Route::post('/update', [ContactController::class, 'update'])
                ->middleware(permission_middleware('contact', 'update'));
            Route::post('/delete', [ContactController::class, 'destroy'])
                ->middleware(permission_middleware('contact', 'delete'));
        });
        
        // Enquiry actions
        Route::prefix('enquiries')->group(function () {
            Route::post('/create', [EnquiryController::class, 'create'])
                ->middleware(permission_middleware('enquiry', 'create'));
            Route::post('/update', [EnquiryController::class, 'update'])
                ->middleware(permission_middleware('enquiry', 'update'));
            Route::post('/delete', [EnquiryController::class, 'delete'])
                ->middleware(permission_middleware('enquiry', 'delete'));
        });
        
        // Purchase actions
        Route::prefix('purchases')->group(function () {
            Route::post('/create', [PurchaseController::class, 'create'])
                ->middleware(permission_middleware('purchase', 'create'));
            Route::post('/update', [PurchaseController::class, 'update'])
                ->middleware(permission_middleware('purchase', 'update'));
            Route::post('/delete', [PurchaseController::class, 'delete'])
                ->middleware(permission_middleware('purchase', 'delete'));
        });
        
        // Sale actions
        Route::prefix('sales')->group(function () {
            Route::post('/create', [SaleController::class, 'create'])
                ->middleware(permission_middleware('sale', 'create'));
            Route::post('/update', [SaleController::class, 'update'])
                ->middleware(permission_middleware('sale', 'update'));
            Route::post('/delete', [SaleController::class, 'delete'])
                ->middleware(permission_middleware('sale', 'delete'));
        });
        
        // Expense actions
        Route::prefix('expenses')->group(function () {
            Route::post('/create', [ExpenseController::class, 'create'])
                ->middleware(permission_middleware('expense', 'create'));
            Route::post('/update', [ExpenseController::class, 'update'])
                ->middleware(permission_middleware('expense', 'update'));
            Route::post('/delete', [ExpenseController::class, 'delete'])
                ->middleware(permission_middleware('expense', 'delete'));
        });
        
        // Financial Account actions
        Route::prefix('financial-accounts')->group(function () {
            Route::post('/create', [FinancialAccountController::class, 'create'])
                ->middleware(permission_middleware('financial-account', 'create'));
            Route::post('/update', [FinancialAccountController::class, 'update'])
                ->middleware(permission_middleware('financial-account', 'update'));
            Route::post('/delete', [FinancialAccountController::class, 'delete'])
                ->middleware(permission_middleware('financial-account', 'delete'));
        });
        
        // Transaction actions
        Route::prefix('transactions')->group(function () {
            Route::post('/create', [TransactionController::class, 'create'])
                ->middleware(permission_middleware('transaction', 'create'));
            Route::post('/update', [TransactionController::class, 'update'])
                ->middleware(permission_middleware('transaction', 'update'));
            Route::post('/delete', [TransactionController::class, 'delete'])
                ->middleware(permission_middleware('transaction', 'delete'));
        });
        
        // Account/Profile actions
        Route::prefix('account')->group(function () {
            Route::post('/update-profile', function () {
                // TODO: Implement update profile
                return response()->json(['message' => 'Update profile endpoint - to be implemented'], 501);
            });
        });
    });
});


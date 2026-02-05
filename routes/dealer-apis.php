<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\DealerProfileController;
use App\Http\Controllers\DealerStaffController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DealerLookupController;
use App\Http\Controllers\DealerDashboardController;
use App\Http\Controllers\DealerEnquiryController;
use App\Http\Controllers\DealerAuditLogController;

/*
|--------------------------------------------------------------------------
| Dealer API Routes - Version 1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/dealer/* via bootstrap/app.php
| All routes require auth:api middleware (standardized)
|
*/

// Dealer routes (requires authentication - standardized to auth:api)
Route::middleware('auth:api')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DealerDashboardController::class, 'index'])
        ->middleware('permission:dealer.dashboard.view');
    
    // Vehicle Management
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'dealerIndex'])
            ->middleware('permission:dealer.vehicles.view');
        
        Route::get('/show/{id}', [VehicleController::class, 'show'])
            ->middleware('permission:dealer.vehicles.view');
        
        Route::post('/', [VehicleController::class, 'store'])
            ->middleware(['throttle:20,1', 'idempotency', 'permission:dealer.vehicles.create']);
        
        Route::post('/draft', [VehicleController::class, 'storeDraft'])
            ->middleware('permission:dealer.vehicles.create');
        
        Route::post('/update/{id}', [VehicleController::class, 'update'])
            ->middleware('permission:dealer.vehicles.update');
        
        Route::post('/delete/{id}', [VehicleController::class, 'destroy'])
            ->middleware('permission:dealer.vehicles.delete');
        
        Route::post('/update-status/{id}', [VehicleController::class, 'updateStatus'])
            ->middleware('permission:dealer.vehicles.status');
        
        Route::post('/update-equipment/{id}', [VehicleController::class, 'updateEquipment'])
            ->middleware('permission:dealer.vehicles.update');
        
        Route::post('/{id}/images', [VehicleController::class, 'uploadImages'])
            ->middleware('permission:dealer.vehicles.media');
        
        Route::delete('/{id}/images/{imageId}', [VehicleController::class, 'deleteImage'])
            ->middleware('permission:dealer.vehicles.media');
        
        Route::put('/{id}/price', [VehicleController::class, 'updatePrice'])
            ->middleware('permission:dealer.vehicles.update');
        
        Route::post('/fetch-from-nummerplade', [VehicleController::class, 'fetchFromNummerplade'])
            ->middleware(['throttle:40,1', 'permission:dealer.vehicles.create']);
    });
    
    // Lookup endpoints (for form dropdowns and vehicle lookup)
    Route::get('/lookup-constants', [DealerLookupController::class, 'getLookupConstants']);
    Route::prefix('lookup')->group(function () {
        Route::post('/vehicle-by-registration', [DealerLookupController::class, 'lookupVehicleByRegistration'])
            ->middleware('throttle:40,1'); // Rate limit vehicle lookups
    });
    
    // Lead Management
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index'])
            ->middleware('permission:dealer.leads.view');
        
        Route::get('show/{id}', [LeadController::class, 'show'])
            ->middleware('permission:dealer.leads.view');
        
        Route::post('assign/{id}', [LeadController::class, 'assign'])
            ->middleware('permission:dealer.leads.assign');
        
        Route::post('stage/{id}', [LeadController::class, 'updateStage'])
            ->middleware('permission:dealer.leads.update');
        
        Route::post('intent/{id}', [LeadController::class, 'updateIntent'])
            ->middleware('permission:dealer.leads.update');
        
        Route::post('category/{id}', [LeadController::class, 'updateCategory'])
            ->middleware('permission:dealer.leads.update');
        
        Route::get('messages/{id}', [LeadController::class, 'getMessages'])
            ->middleware('permission:dealer.leads.messages');
        
        Route::post('messages/{id}', [LeadController::class, 'sendMessage'])
            ->middleware('permission:dealer.leads.messages');
    });
    
    // Enquiry Management
    Route::prefix('enquiries')->group(function () {
        Route::get('/', [DealerEnquiryController::class, 'index']);
        
        Route::get('show/{id}', [DealerEnquiryController::class, 'show']);
        
        Route::post('status/{id}', [DealerEnquiryController::class, 'updateStatus']);
        
        Route::post('type/{id}', [DealerEnquiryController::class, 'updateType']);
    });
    
    // Dealer Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [DealerProfileController::class, 'show']);
        Route::post('/update', [DealerProfileController::class, 'update']);
    });
    
    // Dealer Staff
    Route::prefix('staff')->group(function () {
        Route::get('/', [DealerStaffController::class, 'index'])
            ->middleware('permission:dealer.staff.manage');
        Route::post('/', [DealerStaffController::class, 'store'])
            ->middleware('permission:dealer.staff.manage');
        Route::put('/{userId}', [DealerStaffController::class, 'update'])
            ->middleware('permission:dealer.staff.manage');
        Route::delete('/{userId}', [DealerStaffController::class, 'destroy'])
            ->middleware('permission:dealer.staff.manage');
    });
    
    // Subscriptions (admin only)
    Route::prefix('subscription')->group(function () {
        Route::get('/', [SubscriptionController::class, 'show'])
            ->middleware('permission:dealer.subscription.manage');
        Route::get('/features', [SubscriptionController::class, 'getFeatures'])
            ->middleware('permission:dealer.subscription.manage');
        Route::get('/history', [SubscriptionController::class, 'getHistory'])
            ->middleware('permission:dealer.subscription.manage');
        Route::post('/', [SubscriptionController::class, 'store'])
            ->middleware('permission:dealer.subscription.manage');
    });
    
    // Available Plans
    Route::get('/plans', [SubscriptionController::class, 'getAvailablePlans'])
        ->middleware('permission:dealer.subscription.manage');
    
    // Audit Logs (admin only)
    Route::prefix('audit-logs')->group(function () {
        Route::get('/', [DealerAuditLogController::class, 'index'])
            ->middleware('permission:dealer.audit.view');
    });
});

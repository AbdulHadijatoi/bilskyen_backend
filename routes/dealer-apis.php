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

/*
|--------------------------------------------------------------------------
| Dealer API Routes - Version 1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/dealer/* via bootstrap/app.php
| All routes require auth:api middleware (standardized)
|
*/

// Helper function to apply permission middleware with correct syntax
if (!function_exists('permission_middleware')) {
    function permission_middleware($permission, $action) {
        return 'permission:' . $permission . ',' . $action;
    }
}

// Dealer routes (requires authentication - standardized to auth:api)
Route::middleware('auth:api')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DealerDashboardController::class, 'index']);
    
    // Vehicle Management
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'dealerIndex']);
            // ->middleware(permission_middleware('vehicle', 'list'));
        
        Route::get('/show/{id}', [VehicleController::class, 'show']);
            // ->middleware(permission_middleware('vehicle', 'view'));
        
        Route::post('/', [VehicleController::class, 'store']);  
            // ->middleware(['throttle:20,1', 'idempotency', permission_middleware('vehicle', 'create')]);
        
        Route::post('/draft', [VehicleController::class, 'storeDraft']);
            // ->middleware(permission_middleware('vehicle', 'create'));
        
        Route::post('/update/{id}', [VehicleController::class, 'update']);
            // ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::post('/delete/{id}', [VehicleController::class, 'destroy']);
            // ->middleware(permission_middleware('vehicle', 'delete'));
        
        Route::post('/update-status/{id}', [VehicleController::class, 'updateStatus']);
            // ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::post('/update-equipment/{id}', [VehicleController::class, 'updateEquipment']);
            // ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::post('/{id}/images', [VehicleController::class, 'uploadImages']);
            // ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::delete('/{id}/images/{imageId}', [VehicleController::class, 'deleteImage']);
            // ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::put('/{id}/price', [VehicleController::class, 'updatePrice']);
            // ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::post('/fetch-from-nummerplade', [VehicleController::class, 'fetchFromNummerplade'])
            ->middleware(['throttle:40,1', permission_middleware('vehicle', 'create')]);
    });
    
    // Lookup endpoints (for form dropdowns and vehicle lookup)
    Route::get('/lookup-constants', [DealerLookupController::class, 'getLookupConstants']);
    Route::prefix('lookup')->group(function () {
        Route::post('/vehicle-by-registration', [DealerLookupController::class, 'lookupVehicleByRegistration'])
            ->middleware('throttle:40,1'); // Rate limit vehicle lookups
    });
    
    // Lead Management
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index']);
        
        Route::get('show/{id}', [LeadController::class, 'show']);
        
        Route::post('assign/{id}', [LeadController::class, 'assign']);
        
        Route::post('stage/{id}', [LeadController::class, 'updateStage']);
        
        Route::get('messages/{id}', [LeadController::class, 'getMessages']);
        
        Route::post('messages/{id}', [LeadController::class, 'sendMessage']);
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
        Route::get('/', [DealerStaffController::class, 'index']);
        Route::post('/', [DealerStaffController::class, 'store']);
        Route::put('/{userId}', [DealerStaffController::class, 'update']);
        Route::delete('/{userId}', [DealerStaffController::class, 'destroy']);
    });
    
    // Subscriptions
    Route::prefix('subscription')->group(function () {
        Route::get('/', [SubscriptionController::class, 'show']);
        Route::get('/features', [SubscriptionController::class, 'getFeatures']);
        Route::get('/history', [SubscriptionController::class, 'getHistory']);
    });
});

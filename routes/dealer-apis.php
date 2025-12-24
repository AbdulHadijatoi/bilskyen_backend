<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\SavedSearchController;
use App\Http\Controllers\DealerProfileController;
use App\Http\Controllers\DealerStaffController;
use App\Http\Controllers\SubscriptionController;

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
    
    // Vehicle Management
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])
            ->middleware(permission_middleware('vehicle', 'list'));
        
        Route::get('/{id}', [VehicleController::class, 'show'])
            ->middleware(permission_middleware('vehicle', 'view'));
        
        Route::post('/', [VehicleController::class, 'store'])
            ->middleware(['throttle:10,1', 'idempotency', permission_middleware('vehicle', 'create')]);
        
        Route::put('/{id}', [VehicleController::class, 'update'])
            ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::delete('/{id}', [VehicleController::class, 'destroy'])
            ->middleware(permission_middleware('vehicle', 'delete'));
        
        Route::post('/{id}/images', [VehicleController::class, 'uploadImages'])
            ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::delete('/{id}/images/{imageId}', [VehicleController::class, 'deleteImage'])
            ->middleware(permission_middleware('vehicle', 'update'));
        
        // Single status endpoint (replaces publish/unpublish)
        Route::put('/{id}/status', [VehicleController::class, 'updateStatus'])
            ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::put('/{id}/price', [VehicleController::class, 'updatePrice'])
            ->middleware(permission_middleware('vehicle', 'update'));
        
        Route::post('/fetch-from-nummerplade', [VehicleController::class, 'fetchFromNummerplade'])
            ->middleware(['throttle:20,1', permission_middleware('vehicle', 'create')]);
    });
    
    // Lead Management
    Route::prefix('leads')->group(function () {
        Route::get('/', [LeadController::class, 'index'])
            ->middleware(permission_middleware('lead', 'list'));
        
        Route::get('/{id}', [LeadController::class, 'show'])
            ->middleware(permission_middleware('lead', 'view'));
        
        Route::post('/{id}/assign', [LeadController::class, 'assign'])
            ->middleware(permission_middleware('lead', 'update'));
        
        Route::put('/{id}/stage', [LeadController::class, 'updateStage'])
            ->middleware(permission_middleware('lead', 'update'));
        
        Route::get('/{id}/messages', [LeadController::class, 'getMessages'])
            ->middleware(permission_middleware('lead', 'view'));
        
        Route::post('/{id}/messages', [LeadController::class, 'sendMessage'])
            ->middleware(permission_middleware('lead', 'update'));
    });
    
    // Favorites & Saved Searches
    Route::prefix('favorites')->group(function () {
        Route::get('/', [FavoriteController::class, 'index']);
        Route::post('/', [FavoriteController::class, 'store']);
        Route::delete('/{vehicleId}', [FavoriteController::class, 'destroy']);
    });
    
    Route::prefix('saved-searches')->group(function () {
        Route::get('/', [SavedSearchController::class, 'index']);
        Route::post('/', [SavedSearchController::class, 'store']);
        Route::delete('/{id}', [SavedSearchController::class, 'destroy']);
    });
    
    // Dealer Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [DealerProfileController::class, 'show']);
        Route::put('/', [DealerProfileController::class, 'update']);
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

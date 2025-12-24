<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminDealerController;
use App\Http\Controllers\AdminVehicleController;
use App\Http\Controllers\AdminPlanController;
use App\Http\Controllers\AdminFeatureController;
use App\Http\Controllers\AdminSubscriptionController;
use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\AdminBlogController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminAuditLogController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\AdminNotificationController;

/*
|--------------------------------------------------------------------------
| Admin API Routes - Version 1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/admin/* via bootstrap/app.php
| All routes require auth:api and role:admin middleware (standardized)
|
*/

// Admin routes (requires authentication and admin role - standardized to auth:api)
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [AdminUserController::class, 'index']);
        Route::get('/{id}', [AdminUserController::class, 'show']);
        Route::post('/', [AdminUserController::class, 'store'])
            ->middleware(['idempotency']); // Idempotency for user creation
        Route::put('/{id}', [AdminUserController::class, 'update']);
        Route::delete('/{id}', [AdminUserController::class, 'destroy']); // Soft delete
        Route::put('/{id}/status', [AdminUserController::class, 'updateStatus']);
        Route::put('/{id}/ban', [AdminUserController::class, 'ban']);
        Route::put('/{id}/unban', [AdminUserController::class, 'unban']);
    });
    
    // Dealer Management
    Route::prefix('dealers')->group(function () {
        Route::get('/', [AdminDealerController::class, 'index']);
        Route::get('/{id}', [AdminDealerController::class, 'show']);
        Route::post('/', [AdminDealerController::class, 'store'])
            ->middleware(['idempotency']); // Idempotency for dealer creation
        Route::put('/{id}', [AdminDealerController::class, 'update']);
        Route::delete('/{id}', [AdminDealerController::class, 'destroy']); // Soft delete
    });
    
    // Vehicle Management (Admin can see all dealer listings)
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [AdminVehicleController::class, 'index']);
        Route::get('/{id}', [AdminVehicleController::class, 'show']);
        Route::put('/{id}/status', [AdminVehicleController::class, 'updateStatus']); // Single status endpoint
        Route::delete('/{id}', [AdminVehicleController::class, 'destroy']); // Soft delete
        Route::get('/{id}/history', [AdminVehicleController::class, 'getHistory']);
    });
    
    // Plan & Subscription Management
    Route::prefix('plans')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index']);
        Route::get('/{id}', [AdminPlanController::class, 'show']);
        Route::post('/', [AdminPlanController::class, 'store'])
            ->middleware(['idempotency']); // Idempotency for plan creation
        Route::put('/{id}', [AdminPlanController::class, 'update']);
        Route::delete('/{id}', [AdminPlanController::class, 'destroy']); // Soft delete
        Route::get('/{id}/features', [AdminPlanController::class, 'getFeatures']);
        Route::post('/{id}/features', [AdminPlanController::class, 'assignFeature']);
        Route::delete('/{id}/features/{featureId}', [AdminPlanController::class, 'removeFeature']);
    });
    
    Route::prefix('subscriptions')->group(function () {
        Route::get('/', [AdminSubscriptionController::class, 'index']);
        Route::post('/', [AdminSubscriptionController::class, 'store']);
        Route::put('/{id}/status', [AdminSubscriptionController::class, 'updateStatus']);
    });
    
    // Feature Management
    Route::prefix('features')->group(function () {
        Route::get('/', [AdminFeatureController::class, 'index']);
        Route::get('/{id}', [AdminFeatureController::class, 'show']);
        Route::post('/', [AdminFeatureController::class, 'store']);
        Route::put('/{id}', [AdminFeatureController::class, 'update']);
        Route::delete('/{id}', [AdminFeatureController::class, 'destroy']);
    });
    
    // CMS Management
    Route::prefix('pages')->group(function () {
        Route::get('/', [AdminPageController::class, 'index']);
        Route::get('/{id}', [AdminPageController::class, 'show']);
        Route::post('/', [AdminPageController::class, 'store']);
        Route::put('/{id}', [AdminPageController::class, 'update']);
        Route::delete('/{id}', [AdminPageController::class, 'destroy']);
        Route::put('/{id}/publish', [AdminPageController::class, 'publish']);
    });
    
    Route::prefix('blogs')->group(function () {
        Route::get('/', [AdminBlogController::class, 'index']);
        Route::get('/{id}', [AdminBlogController::class, 'show']);
        Route::post('/', [AdminBlogController::class, 'store']);
        Route::put('/{id}', [AdminBlogController::class, 'update']);
        Route::delete('/{id}', [AdminBlogController::class, 'destroy']);
    });
    
    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/vehicles', [AdminAnalyticsController::class, 'vehicles']);
        Route::get('/leads', [AdminAnalyticsController::class, 'leads']);
        Route::get('/subscriptions', [AdminAnalyticsController::class, 'subscriptions']);
    });
    
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);
    
    // Notifications
    Route::get('/notifications', [AdminNotificationController::class, 'getNotifications']);
    
    // Permission Management (keep existing)
    Route::prefix('permissions')->group(function () {
        Route::post('/get-all-items', [PermissionManagementController::class, 'getAllItems']);
        Route::post('/get-models', [PermissionManagementController::class, 'getModels']);
        Route::post('/model-items', [PermissionManagementController::class, 'modelItems']);
        Route::post('/assign', [PermissionManagementController::class, 'assign']);
        Route::post('/revoke', [PermissionManagementController::class, 'revoke']);
    });
});

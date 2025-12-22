<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\PermissionManagementController;

/*
|--------------------------------------------------------------------------
| Admin API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for admin functionality.
| These routes are loaded within the "api" middleware group.
|
*/

// Helper function to apply permission middleware with correct syntax
if (!function_exists('permission_middleware')) {
    function permission_middleware($permission, $action) {
        return 'permission:' . $permission . ',' . $action;
    }
}

// Admin routes (requires authentication)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/get-notifications', [AdminNotificationController::class, 'getNotifications'])
        ->middleware(permission_middleware('notification', 'list'));
    
    Route::get('/get-users', [AdminUserController::class, 'getUsers'])
        ->middleware(permission_middleware('user', 'list'));
    
    // Permission management routes
    Route::prefix('permissions')->group(function () {
        Route::post('/get-all-items', [PermissionManagementController::class, 'getAllItems'])
            ->middleware(permission_middleware('permission', 'list'));
        
        Route::post('/get-models', [PermissionManagementController::class, 'getModels'])
            ->middleware(permission_middleware('permission', 'list'));
        
        Route::post('/model-items', [PermissionManagementController::class, 'modelItems'])
            ->middleware(permission_middleware('permission', 'view'));
        
        Route::post('/assign', [PermissionManagementController::class, 'assign'])
            ->middleware(permission_middleware('permission', 'assign'));
        
        Route::post('/revoke', [PermissionManagementController::class, 'revoke'])
            ->middleware(permission_middleware('permission', 'assign'));
    });
});


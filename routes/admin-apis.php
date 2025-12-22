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

// Admin routes (requires authentication and admin role)
Route::middleware(['jwt.auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/get-notifications', [AdminNotificationController::class, 'getNotifications']);
    
    Route::get('/get-users', [AdminUserController::class, 'getUsers']);
    
    // Permission management routes
    Route::prefix('permissions')->group(function () {
        Route::post('/get-all-items', [PermissionManagementController::class, 'getAllItems']);
        
        Route::post('/get-models', [PermissionManagementController::class, 'getModels']);
        
        Route::post('/model-items', [PermissionManagementController::class, 'modelItems']);
        
        Route::post('/assign', [PermissionManagementController::class, 'assign']);
        
        Route::post('/revoke', [PermissionManagementController::class, 'revoke']);
    });
});


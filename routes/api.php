<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
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

// Authentication routes
Route::prefix('auth')->group(function () {
    // JWT Authentication routes (public)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // JWT Protected routes
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
    
    // User session management routes (JWT authenticated)
    Route::middleware('jwt.auth')->group(function () {
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
    
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('jwt.auth');
    
    Route::get('/verify-email', function () {
        return response()->json(['message' => 'Verify email endpoint - to be implemented'], 501);
    });
    
    Route::post('/change-email', function () {
        return response()->json(['message' => 'Change email endpoint - to be implemented'], 501);
    })->middleware('jwt.auth');
});


<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\NummerpladeController;
use App\Http\Controllers\LookupController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1 for versioning
| This prevents breaking changes for mobile apps and external clients
|
*/

// All routes are automatically prefixed with /api via bootstrap/app.php
// We add /v1 prefix here for versioning
Route::prefix('v1')->group(function () {
    // Public routes
    Route::get('/version.json', [VersionController::class, 'getVersion']);
    
    // Public vehicle listings (uses database data)
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/{id}', [VehicleController::class, 'show'])->name('vehicles.show');
    
    // Sell Your Car API (authenticated users can create vehicle listings)
    Route::post('/sell-your-car', [VehicleController::class, 'sellYourCar'])
        ->middleware('auth:api')
        ->name('api.sell-your-car');
    
    // Lookup routes
    Route::get('/locations', [LookupController::class, 'locations']);
    Route::get('/fuel-types', [LookupController::class, 'fuelTypes']);
    Route::get('/transmissions', [LookupController::class, 'transmissions']);
    Route::get('/models', [LookupController::class, 'models']);
    
    // Authentication routes
    Route::prefix('auth')->group(function () {
        // Public auth routes with rate limiting
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware(['throttle:6,1', 'idempotency']); // 6 requests per minute, idempotency
        
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:10,1'); // 10 requests per minute
        
        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:20,1'); // 20 requests per minute
        
        // Protected routes (use auth:api middleware - standardized)
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/sign-out', [AuthController::class, 'signOut']);
            Route::get('/get-session', [AuthController::class, 'getSession']);
            Route::post('/update-user', [AuthController::class, 'updateUser']);
            Route::post('/revoke-session', [AuthController::class, 'revokeSession']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
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
        
        Route::get('/verify-email', function () {
            return response()->json(['message' => 'Verify email endpoint - to be implemented'], 501);
        });
        
        Route::post('/change-email', function () {
            return response()->json(['message' => 'Change email endpoint - to be implemented'], 501);
        })->middleware('auth:api');
    });
    
    // Nummerplade API proxy routes (for Flutter/Vue.js)
    Route::prefix('nummerplade')->group(function () {
        // Vehicle lookup endpoints (rate limited)
        Route::post('/vehicle-by-registration', [NummerpladeController::class, 'getVehicleByRegistration'])
            ->middleware('throttle:40,1'); // 40 requests per minute per IP
        
        Route::post('/vehicle-by-vin', [NummerpladeController::class, 'getVehicleByVin'])
            ->middleware('throttle:40,1'); // 40 requests per minute per IP
        
        // Reference data (cached, less restrictive)
        Route::get('/reference/body-types', [NummerpladeController::class, 'getBodyTypes']);
        Route::get('/reference/colors', [NummerpladeController::class, 'getColors']);
        Route::get('/reference/fuel-types', [NummerpladeController::class, 'getFuelTypes']);
        Route::get('/reference/equipment', [NummerpladeController::class, 'getEquipment']);
        Route::get('/reference/permits', [NummerpladeController::class, 'getPermits']);
        Route::get('/reference/types', [NummerpladeController::class, 'getTypes']);
        Route::get('/reference/uses', [NummerpladeController::class, 'getUses']);
        
        // Additional data endpoints (rate limited)
        Route::get('/inspections/{vehicleId}', [NummerpladeController::class, 'getInspections'])
            ->middleware('throttle:20,1'); // 20 requests per minute per IP
        
        Route::get('/dmr/{vehicleId}', [NummerpladeController::class, 'getDmrData'])
            ->middleware('throttle:20,1');
        
        Route::get('/debt/{vehicleId}', [NummerpladeController::class, 'getDebt'])
            ->middleware('throttle:20,1');
        
        Route::get('/tinglysning/{vin}', [NummerpladeController::class, 'getTinglysning'])
            ->middleware('throttle:20,1');
        
        Route::get('/emissions/{input}', [NummerpladeController::class, 'getEmissions'])
            ->middleware('throttle:20,1');
        
        Route::get('/evaluations/{input}', [NummerpladeController::class, 'getEvaluations'])
            ->middleware('throttle:20,1');
    });
});

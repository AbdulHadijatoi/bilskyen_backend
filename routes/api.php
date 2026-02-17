<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\NummerpladeController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\HomePageContentController;
use App\Http\Controllers\PageContentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    Route::post('/search-vehicles', [VehicleController::class, 'searchVehicles'])->name('vehicles.search');

    Route::get('/vehicles/{id}', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::get('/featured-vehicles', [VehicleController::class, 'getFeaturedVehicles'])->name('vehicles.featured');
    
    // Sell Your Car API (authenticated users can create vehicle listings)
    Route::post('/sell-your-car', [VehicleController::class, 'sellYourCar'])
        ->middleware(['auth:api', 'idempotency'])
        ->name('api.sell-your-car');
    
    
    // Constants API - Get all lookup tables data
    Route::get('/constants', [LookupController::class, 'constants'])->name('constants');
    
    // Home Page Content API (public, uses cache)
    Route::get('/home-page-content', [HomePageContentController::class, 'getHomePageContent'])->name('home-page-content');

    // Privacy & Terms page content (public, cached; same content as Blade views: privacy_body / terms_body)
    Route::get('/privacy-policy', [PageContentController::class, 'getPrivacyContent']);
    Route::get('/terms-of-service', [PageContentController::class, 'getTermsContent']);

    // Authentication routes
    Route::prefix('auth')->group(function () {
        // Public auth routes with isolated rate limiting (named limiters prevent cross-endpoint interference)
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware(['throttle:auth.register', 'idempotency']); // 6 requests per minute, idempotency
        
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:auth.login'); // 10 requests per minute
        
        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:auth.refresh'); // 20 requests per minute
        
        // Panel login endpoints (for Vue.js admin panel - restricted to dealer/staff/admin roles)
        Route::post('/panel-login', [AuthController::class, 'panelLogin'])
            ->middleware('throttle:auth.login'); // 10 requests per minute
        
        Route::post('/panel-refresh', [AuthController::class, 'panelRefresh'])
            ->middleware('throttle:auth.refresh'); // 20 requests per minute
        
        // Staff login endpoint (for dealer staff members using username)
        Route::post('/staff-login', [AuthController::class, 'staffLogin'])
            ->middleware('throttle:auth.login'); // 10 requests per minute
        
        // Protected routes (use auth:api middleware - standardized)
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/sign-out', [AuthController::class, 'signOut']);
            Route::get('/get-session', [AuthController::class, 'getSession']);
            Route::post('/update-user', [AuthController::class, 'updateUser']);
            Route::post('/profile', [AuthController::class, 'updateUser']); // Alias for update-user
            Route::post('/revoke-session', [AuthController::class, 'revokeSession']);
            Route::post('/change-password', [AuthController::class, 'changePassword']);
            Route::delete('/account', [AuthController::class, 'deleteAccount']);
        });
        
        // TODO: Implement remaining endpoints
        Route::post('/sign-in/magic-link', function () {
            return response()->json(['message' => 'Magic link endpoint - to be implemented'], 501);
        });
        
        Route::get('/verify-magic-link', function () {
            return response()->json(['message' => 'Verify magic link endpoint - to be implemented'], 501);
        });
        
        Route::post('/forget-password', [AuthController::class, 'forgetPassword'])
            ->middleware('throttle:auth.login'); // 10 requests per minute
        
        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:auth.login'); // 10 requests per minute
        
        Route::get('/verify-email', function () {
            return response()->json(['message' => 'Verify email endpoint - to be implemented'], 501);
        });
        
        Route::post('/change-email', function () {
            return response()->json(['message' => 'Change email endpoint - to be implemented'], 501);
        })->middleware('auth:api');
    });
    
    // Favorites API routes (for authenticated users)
    Route::middleware('auth:api')->prefix('favorites')->group(function () {
        Route::get('/', [\App\Http\Controllers\FavoriteController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\FavoriteController::class, 'store']);
        Route::post('delete/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'destroy']);
        Route::get('/check/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'check']);
        Route::post('/check-batch', [\App\Http\Controllers\FavoriteController::class, 'checkBatch']);
    });
    
    Route::middleware('auth:api')->prefix('vehicles')->group(function () {
        Route::post('/{id}/leads', [\App\Http\Controllers\EnquiryController::class, 'enquire']);
        Route::post('/{id}/enquiries', [\App\Http\Controllers\EnquiryController::class, 'submitEnquiryForm']);
        Route::post('/{id}/test-drive', [\App\Http\Controllers\EnquiryController::class, 'submitTestDriveForm']);
        Route::post('/{id}/price-negotiation', [\App\Http\Controllers\EnquiryController::class, 'submitPriceNegotiationForm']);
    });
    
    // Seller Profile API routes (for authenticated sellers)
    Route::middleware('auth:api')->prefix('seller')->group(function () {
        Route::get('/vehicles', [\App\Http\Controllers\SellerProfileController::class, 'getVehicles']);
        Route::get('/vehicles/{id}', [\App\Http\Controllers\SellerProfileController::class, 'getVehicle']);
        Route::put('/vehicles/{id}', [\App\Http\Controllers\SellerProfileController::class, 'updateVehicle']);
        Route::patch('/vehicles/{id}/status', [\App\Http\Controllers\SellerProfileController::class, 'updateVehicleStatus']);
        Route::delete('/vehicles/{id}', [\App\Http\Controllers\SellerProfileController::class, 'deleteVehicle']);
        Route::get('/inquiries', [\App\Http\Controllers\SellerProfileController::class, 'getInquiries']);
        Route::get('/inquiries/{id}', [\App\Http\Controllers\SellerProfileController::class, 'getInquiry']);
        Route::get('/statistics', [\App\Http\Controllers\SellerProfileController::class, 'getStatistics']);
    });
    
    // Dealer Profile API routes (for authenticated dealers)
    Route::middleware('auth:api')->prefix('dealer')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\DealerProfileApiController::class, 'getProfile']);
        Route::get('/vehicles', [\App\Http\Controllers\DealerProfileApiController::class, 'getVehicles']);
        Route::post('/enquiry', [\App\Http\Controllers\DealerProfileApiController::class, 'sendEnquiry']);
        Route::get('/statistics', [\App\Http\Controllers\DealerProfileApiController::class, 'getStatistics']);
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

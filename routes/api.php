<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\DmrFactVehicleLookupController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\HomePageContentController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\SellYourCarController;
use App\Http\Controllers\VehicleFeedController;
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
    Route::get('/vehicles/count', [VehicleController::class, 'count'])->name('vehicles.count');
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::post('/search-vehicles', [VehicleController::class, 'searchVehicles'])->name('vehicles.search');

    Route::get('/vehicles/{id}', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::get('/featured-vehicles', [VehicleController::class, 'getFeaturedVehicles'])->name('vehicles.featured');

    Route::get('/feeds/{token}/vehicles.json', [VehicleFeedController::class, 'json']);
    Route::get('/feeds/{token}/vehicles.xml', [VehicleFeedController::class, 'xml']);

    Route::prefix('finance')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\PublicFinanceController::class, 'settings']);
        Route::post('/calculate', [\App\Http\Controllers\PublicFinanceController::class, 'calculate']);
        Route::get('/vehicles/{vehicle}/widget', [\App\Http\Controllers\PublicFinanceController::class, 'vehicleWidget']);
    });

    Route::prefix('marketing')->group(function () {
        Route::post('/consent', [\App\Http\Controllers\PublicMarketingController::class, 'logConsent']);
        Route::post('/unsubscribe', [\App\Http\Controllers\PublicMarketingController::class, 'unsubscribe']);
        Route::post('/abandoned/track', [\App\Http\Controllers\PublicMarketingController::class, 'trackAbandoned']);
    });

    Route::post('/gdpr/export-request', [\App\Http\Controllers\PublicGdprController::class, 'requestExport']);

    Route::middleware('dealer.api.key')->prefix('dms')->group(function () {
        Route::post('/vehicles/upsert', [\App\Http\Controllers\DmsInboundController::class, 'upsertVehicle']);
    });
    
    // Sell Your Car API — same create logic as web /sell-your-car (SellYourCarController::store)
    Route::middleware('auth:api')->group(function () {
        Route::get('/sell-your-car/form', [SellYourCarController::class, 'apiFormData'])
            ->name('api.sell-your-car.form');
        Route::post('/sell-your-car', [VehicleController::class, 'sellYourCar'])
            ->middleware('idempotency')
            ->name('api.sell-your-car');
    });
    
    
    // Constants API - Get all lookup tables data
    Route::get('/constants', [LookupController::class, 'constants'])->name('constants');

    // Public lookup search endpoints (partial datasets to reduce `/constants` load)
    Route::get('/brands', [LookupController::class, 'searchBrands'])->name('lookup.brands');
    Route::get('/models', [LookupController::class, 'searchModels'])->name('lookup.models');
    Route::get('/listing-models', [LookupController::class, 'searchListingModels'])->name('lookup.listing_models');
    Route::get('/types', [LookupController::class, 'searchTypes'])->name('lookup.types');
    Route::get('/variants', [LookupController::class, 'searchVariants'])->name('lookup.variants');
    
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
        Route::delete('/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'destroy']);
        Route::get('/check/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'check']);
        Route::post('/check-batch', [\App\Http\Controllers\FavoriteController::class, 'checkBatch']);
    });
    
    // Vehicle enquiry routes - Public (guests and auth users can submit, same as web)
    Route::prefix('vehicles')->group(function () {
        Route::post('/{vehicle}/leads', [\App\Http\Controllers\EnquiryController::class, 'enquire']);
        Route::post('/{vehicle}/enquiries', [\App\Http\Controllers\EnquiryController::class, 'submitEnquiryForm']);
        Route::post('/{vehicle}/test-drive', [\App\Http\Controllers\EnquiryController::class, 'submitTestDriveForm']);
        Route::post('/{vehicle}/price-negotiation', [\App\Http\Controllers\EnquiryController::class, 'submitPriceNegotiationForm']);
        Route::post('/{vehicle}/exchange', [\App\Http\Controllers\EnquiryController::class, 'submitExchangeForm']);
    });
    
    // Seller Profile API routes (for authenticated sellers)
    Route::middleware('auth:api')->prefix('seller')->group(function () {
        Route::get('/vehicles', [\App\Http\Controllers\SellerProfileController::class, 'getVehicles']);
        Route::get('/vehicles/{id}/edit', [\App\Http\Controllers\SellerProfileController::class, 'getVehicleEditForm']);
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
    
    // Local DMR vehicle lookup by registration (slim payload from dmr_* tables)
    Route::prefix('dmr')->group(function () {
        Route::post('/vehicle-by-registration', [DmrFactVehicleLookupController::class, 'lookupByRegistration'])
            ->middleware('throttle:40,1');

        // Manual dropdown search (limited datasets for performance)
        Route::get('/manual-brands', [DmrFactVehicleLookupController::class, 'searchManualBrands']);
        Route::get('/manual-models', [DmrFactVehicleLookupController::class, 'searchManualModels']);
        Route::get('/manual-fuel-types', [DmrFactVehicleLookupController::class, 'searchManualFuelTypes']);

        // Manual -> dmr_fact_vehicle_id resolver (used on submit)
        Route::post('/vehicle-by-manual', [DmrFactVehicleLookupController::class, 'lookupByManual']);
    });
});

// Stripe webhooks (no auth; signature verified in controller)
Route::post('/v1/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle']);

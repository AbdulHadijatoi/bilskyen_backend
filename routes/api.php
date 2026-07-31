<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VersionController;
use App\Http\Controllers\DmrFactVehicleLookupController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\HomePageContentController;
use App\Http\Controllers\PageContentController;
use App\Http\Controllers\PublicPlatformController;
use App\Http\Controllers\PublicPlansController;
use App\Http\Controllers\SellYourCarController;
use App\Http\Controllers\VehicleFeedController;

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
    Route::get('/version.json', [VersionController::class, 'getVersion'])
        ->middleware('throttle:public.reads');
    Route::get('/platform/ui-settings', [PublicPlatformController::class, 'uiSettings'])
        ->middleware('throttle:public.reads');
    
    // Public vehicle listings (uses database data)
    Route::middleware(['throttle:public.listings', 'abuse.detect'])->group(function () {
        Route::get('/vehicles/count', [VehicleController::class, 'count'])->name('vehicles.count');
        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::post('/search-vehicles', [VehicleController::class, 'searchVehicles'])->name('vehicles.search');
        Route::get('/featured-vehicles', [VehicleController::class, 'getFeaturedVehicles'])->name('vehicles.featured');
    });

    Route::get('/vehicles/{id}', [VehicleController::class, 'show'])
        ->middleware(['throttle:public.reads', 'abuse.detect'])
        ->name('vehicles.show');

    Route::middleware(['throttle:public.feeds', 'feed.ip', 'abuse.detect'])->group(function () {
        Route::get('/feeds/platform/{token}/vehicles.csv', [VehicleFeedController::class, 'platformCsv']);
        Route::get('/feeds/{token}/vehicles.json', [VehicleFeedController::class, 'json']);
        Route::get('/feeds/{token}/vehicles.xml', [VehicleFeedController::class, 'xml']);
        Route::get('/feeds/{token}/vehicles.csv', [VehicleFeedController::class, 'csv']);
    });

    Route::prefix('finance')->middleware('throttle:public.reads')->group(function () {
        Route::get('/settings', [\App\Http\Controllers\PublicFinanceController::class, 'settings']);
        Route::post('/calculate', [\App\Http\Controllers\PublicFinanceController::class, 'calculate'])
            ->middleware(['throttle:public.writes', 'honeypot']);
        Route::get('/vehicles/{vehicle}/widget', [\App\Http\Controllers\PublicFinanceController::class, 'vehicleWidget']);
    });

    Route::prefix('marketing')->middleware(['throttle:public.writes', 'honeypot'])->group(function () {
        Route::post('/consent', [\App\Http\Controllers\PublicMarketingController::class, 'logConsent'])
            ->middleware('turnstile');
        // Email one-click unsubscribe cannot complete a CAPTCHA challenge.
        Route::post('/unsubscribe', [\App\Http\Controllers\PublicMarketingController::class, 'unsubscribe']);
        // Fired while typing in forms — rate-limit + honeypot only.
        Route::post('/abandoned/track', [\App\Http\Controllers\PublicMarketingController::class, 'trackAbandoned']);
    });

    Route::post('/gdpr/export-request', [\App\Http\Controllers\PublicGdprController::class, 'requestExport'])
        ->middleware(['throttle:public.writes', 'honeypot', 'turnstile']);

    Route::post('/sell-your-car/ai/generate', [\App\Http\Controllers\PublicAiController::class, 'generateListingDescription'])
        ->middleware(['throttle:3,1', 'honeypot', 'turnstile']);

    Route::post('/faq/chat', [\App\Http\Controllers\FaqChatController::class, 'chat'])
        ->middleware(['throttle:20,1', 'honeypot']);

    Route::post('/ai/search-parse', [\App\Http\Controllers\AiSearchController::class, 'parse'])
        ->middleware(['throttle:20,1', 'honeypot']);

    Route::get('/search/suggest', [\App\Http\Controllers\AiSearchController::class, 'suggest'])
        ->middleware(['throttle:public.reads', 'abuse.detect']);

    Route::get('/search/examples', [\App\Http\Controllers\AiSearchController::class, 'examples'])
        ->middleware(['throttle:public.reads']);

    Route::post('/public/listing-health-audit', [\App\Http\Controllers\PublicListingHealthController::class, 'audit'])
        ->middleware('throttle:10,1');

    Route::middleware('dealer.api.key')->prefix('dms')->group(function () {
        Route::post('/vehicles/upsert', [\App\Http\Controllers\DmsInboundController::class, 'upsertVehicle']);
    });
    
    // Sell Your Car API — same create logic as web /sell-your-car (SellYourCarController::store)
    Route::middleware('auth:api')->group(function () {
        Route::get('/sell-your-car/form', [SellYourCarController::class, 'apiFormData'])
            ->name('api.sell-your-car.form');
        Route::post('/sell-your-car', [VehicleController::class, 'sellYourCar'])
            ->middleware(['idempotency', 'throttle:public.writes'])
            ->name('api.sell-your-car');
        Route::post('/saved-searches', [\App\Http\Controllers\AiSearchController::class, 'saveSearch'])
            ->middleware('throttle:public.writes')
            ->name('api.saved-searches.store');
    });
    
    
    // Constants API - Get all lookup tables data
    Route::middleware(['throttle:public.reads', 'abuse.detect'])->group(function () {
        Route::get('/constants', [LookupController::class, 'constants'])->name('constants');
        Route::get('/brands', [LookupController::class, 'searchBrands'])->name('lookup.brands');
        Route::get('/models', [LookupController::class, 'searchModels'])->name('lookup.models');
        Route::get('/listing-models', [LookupController::class, 'searchListingModels'])->name('lookup.listing_models');
        Route::get('/types', [LookupController::class, 'searchTypes'])->name('lookup.types');
        Route::get('/variants', [LookupController::class, 'searchVariants'])->name('lookup.variants');
    });
    
    // Home Page Content API (public, uses cache)
    Route::middleware('throttle:public.reads')->group(function () {
        Route::get('/home-page-content', [HomePageContentController::class, 'getHomePageContent'])->name('home-page-content');
        Route::get('/privacy-policy', [PageContentController::class, 'getPrivacyContent']);
        Route::get('/terms-of-service', [PageContentController::class, 'getTermsContent']);
        Route::prefix('public')->group(function () {
            Route::get('/plans', [PublicPlansController::class, 'index']);
            Route::get('/pricing-faq', [PublicPlansController::class, 'pricingFaq']);
        });
    });

    // Authentication routes
    Route::prefix('auth')->group(function () {
        // Public auth routes with isolated rate limiting (named limiters prevent cross-endpoint interference)
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware(['throttle:auth.register', 'idempotency', 'honeypot', 'turnstile']);
        
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware(['throttle:auth.login', 'honeypot', 'turnstile']);
        
        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->middleware('throttle:auth.refresh');
        
        // Panel login endpoints (for Vue.js admin panel - restricted to dealer/staff/admin roles)
        Route::post('/panel-login', [AuthController::class, 'panelLogin'])
            ->middleware(['throttle:auth.login', 'honeypot', 'turnstile']);
        
        Route::post('/panel-refresh', [AuthController::class, 'panelRefresh'])
            ->middleware('throttle:auth.refresh');
        
        // Staff login endpoint (for dealer staff members using username)
        Route::post('/staff-login', [AuthController::class, 'staffLogin'])
            ->middleware(['throttle:auth.login', 'honeypot', 'turnstile']);
        
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
        
        // Magic link, email verification, and change email
        Route::post('/sign-in/magic-link', [AuthController::class, 'signInMagicLink'])
            ->middleware(['throttle:auth.login', 'honeypot', 'turnstile']);

        Route::get('/verify-magic-link', [AuthController::class, 'verifyMagicLink'])
            ->middleware('throttle:auth.login');

        Route::get('/verify-email', [AuthController::class, 'verifyEmail'])
            ->middleware('throttle:auth.login');

        Route::post('/change-email', [AuthController::class, 'changeEmail'])
            ->middleware('auth:api');

        Route::post('/forget-password', [AuthController::class, 'forgetPassword'])
            ->middleware(['throttle:auth.login', 'honeypot', 'turnstile']);

        Route::post('/reset-password', [AuthController::class, 'resetPassword'])
            ->middleware(['throttle:auth.login', 'honeypot', 'turnstile']);
    });
    
    // Favorites API routes (for authenticated users)
    Route::middleware('auth:api')->prefix('favorites')->group(function () {
        Route::get('/', [\App\Http\Controllers\FavoriteController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\FavoriteController::class, 'store']);
        Route::delete('/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'destroy']);
        Route::get('/check/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'check']);
        Route::post('/check-batch', [\App\Http\Controllers\FavoriteController::class, 'checkBatch']);
    });

    Route::middleware('auth:api')->prefix('marketplace-notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\MarketplaceNotificationController::class, 'index']);
        Route::get('/count', [\App\Http\Controllers\MarketplaceNotificationController::class, 'unreadCount']);
        Route::post('/mark-read', [\App\Http\Controllers\MarketplaceNotificationController::class, 'markRead']);
    });
    
    // Vehicle enquiry routes - Public (guests and auth users can submit, same as web)
    Route::prefix('vehicles')->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])->group(function () {
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
        Route::post('/enquiry', [\App\Http\Controllers\DealerProfileApiController::class, 'sendEnquiry'])
            ->middleware('throttle:public.writes');
        Route::get('/statistics', [\App\Http\Controllers\DealerProfileApiController::class, 'getStatistics']);
    });
    
    // Local DMR vehicle lookup by registration (slim payload from dmr_* tables)
    Route::prefix('dmr')->group(function () {
        Route::post('/vehicle-by-registration', [DmrFactVehicleLookupController::class, 'lookupByRegistration'])
            ->middleware(['throttle:40,1', 'abuse.detect']);

        // Manual dropdown search (limited datasets for performance)
        Route::get('/manual-brands', [DmrFactVehicleLookupController::class, 'searchManualBrands'])
            ->middleware(['throttle:public.reads', 'abuse.detect']);
        Route::get('/manual-models', [DmrFactVehicleLookupController::class, 'searchManualModels'])
            ->middleware(['throttle:public.reads', 'abuse.detect']);
        Route::get('/manual-fuel-types', [DmrFactVehicleLookupController::class, 'searchManualFuelTypes'])
            ->middleware(['throttle:public.reads', 'abuse.detect']);

        // Manual -> dmr_fact_vehicle_id resolver (used on submit)
        Route::post('/vehicle-by-manual', [DmrFactVehicleLookupController::class, 'lookupByManual'])
            ->middleware(['throttle:public.writes', 'abuse.detect']);
    });
});

// Stripe webhooks (no auth; signature verified in controller)
Route::post('/v1/webhooks/stripe', [\App\Http\Controllers\StripeWebhookController::class, 'handle']);

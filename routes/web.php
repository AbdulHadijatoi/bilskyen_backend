<?php

use App\Mail\TestMail;
use App\Services\MailService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\CmsPublicController;
use App\Http\Controllers\DmrTestController;
use App\Http\Controllers\DealerMarketingController;
use App\Http\Controllers\LocaleController;

// Locale switcher (session + cookie persistence)
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Sitemap and robots (public, cached)
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Home Page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth Routes
Route::prefix('auth')->group(function () {
    // Login
    Route::get('/login', [AuthPageController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthPageController::class, 'handleLogin'])
        ->middleware(['throttle:auth.login', 'honeypot', 'turnstile'])
        ->name('login.post');
    
    // Signup
    Route::get('/signup', [AuthPageController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthPageController::class, 'handleSignup'])
        ->middleware(['throttle:auth.register', 'honeypot', 'turnstile'])
        ->name('signup.post');
    
    // Forgot Password
    Route::get('/forgot-password', [AuthPageController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthPageController::class, 'handleForgotPassword'])
        ->middleware(['throttle:auth.login', 'honeypot', 'turnstile'])
        ->name('password.email');
    
    // Reset Password
    Route::get('/reset-password', [AuthPageController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthPageController::class, 'handleResetPassword'])
        ->middleware(['throttle:auth.login', 'honeypot', 'turnstile'])
        ->name('password.update');
    
    // Verify Email
    Route::get('/verify-email', [AuthPageController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::post('/verify-email/resend', [AuthPageController::class, 'resendVerificationEmail'])
        ->middleware('throttle:auth.login')
        ->name('verification.send');
    Route::get('/verify-email/{id}/{hash}', [AuthPageController::class, 'verifyEmail'])->name('verification.verify');
    
    // Magic Link Routes
    Route::prefix('magic-link')->group(function () {
        Route::get('/login', [AuthPageController::class, 'showMagicLinkLogin'])->name('magic-link.login');
        Route::post('/login', [AuthPageController::class, 'handleMagicLinkLogin'])
            ->middleware(['throttle:auth.login', 'honeypot', 'turnstile'])
            ->name('magic-link.login.post');
        
        Route::get('/signup', [AuthPageController::class, 'showMagicLinkSignup'])->name('magic-link.signup');
        Route::post('/signup', [AuthPageController::class, 'handleMagicLinkSignup'])
            ->middleware(['throttle:auth.register', 'honeypot', 'turnstile'])
            ->name('magic-link.signup.post');
        
        Route::get('/verify', [AuthPageController::class, 'showMagicLinkVerify'])->name('magic-link.verify');
        Route::post('/verify', [AuthPageController::class, 'handleMagicLinkVerify'])
            ->middleware('throttle:auth.login')
            ->name('magic-link.verify.post');
    });
});

// Logout Route
Route::post('/auth/logout', [AuthPageController::class, 'logout'])->name('logout');
Route::get('/auth/logout', [AuthPageController::class, 'logout'])->name('logout.get');

// Enquiry Routes - Public (guests can submit enquiries)
Route::post('/vehicles/{vehicle}/enquire', [\App\Http\Controllers\EnquiryController::class, 'enquire'])
    ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
    ->name('vehicles.enquire');
Route::get('/vehicles/{vehicle}/enquire', [\App\Http\Controllers\EnquiryController::class, 'showEnquiryForm'])->name('vehicles.enquire.form');
Route::post('/vehicles/{vehicle}/enquire/submit', [\App\Http\Controllers\EnquiryController::class, 'submitEnquiryForm'])
    ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
    ->name('vehicles.enquire.submit');

// Test Drive Routes - Public (guests can submit test drive requests)
Route::get('/vehicles/{vehicle}/test-drive', [\App\Http\Controllers\EnquiryController::class, 'showTestDriveForm'])->name('vehicles.test-drive.form');
Route::post('/vehicles/{vehicle}/test-drive/submit', [\App\Http\Controllers\EnquiryController::class, 'submitTestDriveForm'])
    ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
    ->name('vehicles.test-drive.submit');

// Price Negotiation Routes - Public (guests can submit price negotiations)
Route::get('/vehicles/{vehicle}/price-negotiation', [\App\Http\Controllers\EnquiryController::class, 'showPriceNegotiationForm'])->name('vehicles.price-negotiation.form');
Route::post('/vehicles/{vehicle}/price-negotiation/submit', [\App\Http\Controllers\EnquiryController::class, 'submitPriceNegotiationForm'])
    ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
    ->name('vehicles.price-negotiation.submit');

// Exchange Routes - Public (guests can submit exchange requests)
Route::post('/vehicles/{vehicle}/exchange/submit', [\App\Http\Controllers\EnquiryController::class, 'submitExchangeForm'])
    ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
    ->name('vehicles.exchange.submit');

// Marketplace in-app notifications (cookie auth via AuthService; same pattern as favorites)
Route::get('/marketplace-notifications/count', [\App\Http\Controllers\MarketplaceNotificationController::class, 'unreadCount'])->name('marketplace-notifications.count');
Route::get('/marketplace-notifications', [\App\Http\Controllers\MarketplaceNotificationController::class, 'index'])->name('marketplace-notifications.index');
Route::post('/marketplace-notifications/mark-read', [\App\Http\Controllers\MarketplaceNotificationController::class, 'markRead'])->name('marketplace-notifications.mark-read');

// Authenticated Routes - Require login
Route::middleware('auth.web')->group(function () {
    // Profile Routes
    Route::get('/profile', [HomeController::class, 'showProfile'])->name('profile');
    Route::post('/profile', [HomeController::class, 'updateProfile'])->name('profile.update');
    
    // Favorites Routes
    Route::get('/favorites', [HomeController::class, 'showFavorites'])->name('favorites');
    Route::post('/favorites', [\App\Http\Controllers\FavoriteController::class, 'storeWeb'])->name('favorites.store');
    Route::delete('/favorites/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'destroyWeb'])->name('favorites.destroy');
    Route::get('/favorites/check/{vehicleId}', [\App\Http\Controllers\FavoriteController::class, 'checkWeb'])->name('favorites.check');
    Route::post('/favorites/check-batch', [\App\Http\Controllers\FavoriteController::class, 'checkBatchWeb'])->name('favorites.check.batch');
    Route::post('/saved-searches', [\App\Http\Controllers\AiSearchController::class, 'saveSearchWeb'])
        ->middleware('throttle:public.writes')
        ->name('saved-searches.store');
    
    // Sell Your Car Routes
    Route::get('/sell-your-car', [\App\Http\Controllers\SellYourCarController::class, 'show'])->name('sell-your-car');
    Route::get('/sell-your-car/lookup-context/{dmrFactVehicleId}', [\App\Http\Controllers\SellYourCarController::class, 'lookupContext'])
        ->name('sell-your-car.lookup-context');
    Route::post('/sell-your-car', [\App\Http\Controllers\SellYourCarController::class, 'store'])
        ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
        ->name('sell-your-car.store');
    Route::get('/sell-your-car/success/{token}', [\App\Http\Controllers\SellYourCarController::class, 'showSuccess'])->name('sell-your-car.success');
    Route::post('/sell-your-car/feature/{token}', [\App\Http\Controllers\SellYourCarController::class, 'feature'])->name('sell-your-car.feature');
    
    // Seller Dashboard Routes (Private with encrypted token)
    Route::get('/seller-dashboard/{token}', [\App\Http\Controllers\SellerController::class, 'dashboard'])->name('seller.dashboard');
    Route::get('/seller-dashboard/{token}/vehicle/{id}/edit', [\App\Http\Controllers\SellerController::class, 'edit'])->name('seller.vehicle.edit');
    Route::post('/seller-dashboard/{token}/vehicle/{id}/update', [\App\Http\Controllers\SellerController::class, 'update'])->name('seller.vehicle.update');
    Route::post('/seller-dashboard/{token}/vehicle/{id}/unpublish', [\App\Http\Controllers\SellerController::class, 'unpublish'])->name('seller.vehicle.unpublish');
    Route::delete('/seller-dashboard/{token}/vehicle/{id}', [\App\Http\Controllers\SellerController::class, 'destroy'])->name('seller.vehicle.destroy');
    Route::post('/seller-dashboard/{token}/vehicle/{id}/status', [\App\Http\Controllers\SellerController::class, 'updateStatus'])->name('seller.vehicle.status');
    Route::get('/seller-dashboard/{token}/vehicle/{id}/inquiries', [\App\Http\Controllers\SellerController::class, 'getInquiries'])->name('seller.vehicle.inquiries');
});

// Blog & landing pages
Route::get('/blog', [CmsPublicController::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{slug}', [CmsPublicController::class, 'blogShow'])->name('blog.show');
Route::get('/lp/{slug}', [CmsPublicController::class, 'landingShow'])->name('landing.show');

// Dealer marketing pages
Route::prefix('for-dealers')->name('for-dealers.')->group(function () {
    Route::get('/', [DealerMarketingController::class, 'dealerLanding'])->name('landing');
    Route::get('/pricing', [DealerMarketingController::class, 'dealerPricing'])->name('pricing');
    Route::get('/resources', [DealerMarketingController::class, 'dealerResources'])->name('resources');
    Route::get('/contact', [DealerMarketingController::class, 'dealerContact'])->name('contact');
    Route::post('/contact', [DealerMarketingController::class, 'submitDealerContact'])
        ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
        ->name('contact.submit');
});

// Staff marketing pages
Route::prefix('for-staff')->name('for-staff.')->group(function () {
    Route::get('/', [DealerMarketingController::class, 'staffLanding'])->name('landing');
    Route::get('/resources', [DealerMarketingController::class, 'staffResources'])->name('resources');
});

// About Page
Route::get('/about', [HomeController::class, 'showAbout'])->name('about');

Route::get('/inventory-audit', [\App\Http\Controllers\InventoryAuditController::class, 'show'])->name('inventory-audit');
Route::get('/inventory-audit/{slug}', [\App\Http\Controllers\InventoryAuditController::class, 'brandedShow'])->name('inventory-audit.branded');

// Contact Page
Route::get('/contact', [HomeController::class, 'showContact'])->name('contact');
Route::post('/contact', [HomeController::class, 'submitContact'])
    ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
    ->name('contact.submit');

// Privacy Policy Page
Route::get('/privacy-policy', [HomeController::class, 'showPrivacyPolicy'])->name('privacy-policy');

// FAQ / Help
Route::get('/faq', [HomeController::class, 'showFaq'])->name('faq');

// Terms of Service Page
Route::get('/terms-of-service', [HomeController::class, 'showTermsOfService'])->name('terms-of-service');

// Account deletion (App Store / Play Store compliance)
Route::get('/account-deletion', [HomeController::class, 'showAccountDeletion'])->name('account-deletion');

// City SEO hubs (programmatic local landing pages)
Route::get('/byer', [\App\Http\Controllers\CitySeoController::class, 'index'])->name('cities.index');
Route::get('/biler-i/{city}', [\App\Http\Controllers\CitySeoController::class, 'cars'])->name('cities.cars');
Route::get('/forhandlere-i/{city}', [\App\Http\Controllers\CitySeoController::class, 'dealers'])->name('cities.dealers');

// Vehicles Page (DMR-linked Vehicle records)
Route::get('/vehicles', [HomeController::class, 'showVehicles'])->name('vehicles');

// Vehicle detail (slug)
Route::get('/vehicles/{vehicle}', [HomeController::class, 'showVehicleDetail'])->name('vehicle.detail');

// Dealer Public Page
Route::get('/dealer-{slug}', [\App\Http\Controllers\DealerController::class, 'show'])->name('dealer.show');
Route::post('/dealer-{slug}/enquire', [\App\Http\Controllers\DealerController::class, 'submitEnquiry'])
    ->middleware(['throttle:public.writes', 'honeypot', 'turnstile'])
    ->name('dealer.enquire');
Route::get('/dealer-{slug}/vehicles', [\App\Http\Controllers\DealerController::class, 'getVehicles'])
    ->middleware(['throttle:public.listings', 'abuse.detect'])
    ->name('dealer.vehicles');


if (app()->environment('local', 'testing')) {
    Route::get('/test-mail/7R8e94o4YWW1PnvM', function (MailService $mailService) {
        $to = 'abdulhadijatoi@gmail.com';
        $ok = $mailService->sendMailable(
            $to,
            new TestMail(__('messages.mail.test_body_default')),
            ['mail_type' => 'test'],
            false
        );



        return $ok
            ? 'Test email sent to '.$to
            : 'Failed to send test email (check logs).';
    })->name('test.mail');
}
<?php

use App\Mail\TestMail;
use App\Services\MailService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\DmrTestController;

// Sitemap and robots (public, cached)
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Home Page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Auth Routes
Route::prefix('auth')->group(function () {
    // Login
    Route::get('/login', [AuthPageController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthPageController::class, 'handleLogin'])->name('login.post');
    
    // Signup
    Route::get('/signup', [AuthPageController::class, 'showSignup'])->name('signup');
    Route::post('/signup', [AuthPageController::class, 'handleSignup'])->name('signup.post');
    
    // Forgot Password
    Route::get('/forgot-password', [AuthPageController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthPageController::class, 'handleForgotPassword'])->name('password.email');
    
    // Reset Password
    Route::get('/reset-password', [AuthPageController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthPageController::class, 'handleResetPassword'])->name('password.update');
    
    // Verify Email
    Route::get('/verify-email', [AuthPageController::class, 'showVerifyEmail'])->name('verification.notice');
    Route::post('/verify-email/resend', [AuthPageController::class, 'resendVerificationEmail'])->name('verification.send');
    Route::get('/verify-email/{id}/{hash}', [AuthPageController::class, 'verifyEmail'])->name('verification.verify');
    
    // Magic Link Routes
    Route::prefix('magic-link')->group(function () {
        Route::get('/login', [AuthPageController::class, 'showMagicLinkLogin'])->name('magic-link.login');
        Route::post('/login', [AuthPageController::class, 'handleMagicLinkLogin'])->name('magic-link.login.post');
        
        Route::get('/signup', [AuthPageController::class, 'showMagicLinkSignup'])->name('magic-link.signup');
        Route::post('/signup', [AuthPageController::class, 'handleMagicLinkSignup'])->name('magic-link.signup.post');
        
        Route::get('/verify', [AuthPageController::class, 'showMagicLinkVerify'])->name('magic-link.verify');
        Route::post('/verify', [AuthPageController::class, 'handleMagicLinkVerify'])->name('magic-link.verify.post');
    });
});

// Logout Route
Route::post('/auth/logout', [AuthPageController::class, 'logout'])->name('logout');
Route::get('/auth/logout', [AuthPageController::class, 'logout'])->name('logout.get');

// Enquiry Routes - Public (guests can submit enquiries)
Route::post('/vehicles/{vehicle}/enquire', [\App\Http\Controllers\EnquiryController::class, 'enquire'])->name('vehicles.enquire');
Route::get('/vehicles/{vehicle}/enquire', [\App\Http\Controllers\EnquiryController::class, 'showEnquiryForm'])->name('vehicles.enquire.form');
Route::post('/vehicles/{vehicle}/enquire/submit', [\App\Http\Controllers\EnquiryController::class, 'submitEnquiryForm'])->name('vehicles.enquire.submit');

// Test Drive Routes - Public (guests can submit test drive requests)
Route::get('/vehicles/{vehicle}/test-drive', [\App\Http\Controllers\EnquiryController::class, 'showTestDriveForm'])->name('vehicles.test-drive.form');
Route::post('/vehicles/{vehicle}/test-drive/submit', [\App\Http\Controllers\EnquiryController::class, 'submitTestDriveForm'])->name('vehicles.test-drive.submit');

// Price Negotiation Routes - Public (guests can submit price negotiations)
Route::get('/vehicles/{vehicle}/price-negotiation', [\App\Http\Controllers\EnquiryController::class, 'showPriceNegotiationForm'])->name('vehicles.price-negotiation.form');
Route::post('/vehicles/{vehicle}/price-negotiation/submit', [\App\Http\Controllers\EnquiryController::class, 'submitPriceNegotiationForm'])->name('vehicles.price-negotiation.submit');

// Exchange Routes - Public (guests can submit exchange requests)
Route::post('/vehicles/{vehicle}/exchange/submit', [\App\Http\Controllers\EnquiryController::class, 'submitExchangeForm'])->name('vehicles.exchange.submit');

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
    
    // Sell Your Car Routes
    Route::get('/sell-your-car', [\App\Http\Controllers\SellYourCarController::class, 'show'])->name('sell-your-car');
    Route::get('/sell-your-car/lookup-context/{dmrFactVehicleId}', [\App\Http\Controllers\SellYourCarController::class, 'lookupContext'])
        ->name('sell-your-car.lookup-context');
    Route::post('/sell-your-car', [\App\Http\Controllers\SellYourCarController::class, 'store'])->name('sell-your-car.store');
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

// About Page
Route::get('/about', [HomeController::class, 'showAbout'])->name('about');

// Contact Page
Route::get('/contact', [HomeController::class, 'showContact'])->name('contact');

// Privacy Policy Page
Route::get('/privacy-policy', [HomeController::class, 'showPrivacyPolicy'])->name('privacy-policy');

// Terms of Service Page
Route::get('/terms-of-service', [HomeController::class, 'showTermsOfService'])->name('terms-of-service');

// Account deletion (App Store / Play Store compliance)
Route::get('/account-deletion', [HomeController::class, 'showAccountDeletion'])->name('account-deletion');

// Vehicles Page (DMR-linked Vehicle records)
Route::get('/vehicles', [HomeController::class, 'showVehicles'])->name('vehicles');

// Vehicle detail (slug)
Route::get('/vehicles/{vehicle}', [HomeController::class, 'showVehicleDetail'])->name('vehicle.detail');

// Dealer Public Page
Route::get('/dealer-{slug}', [\App\Http\Controllers\DealerController::class, 'show'])->name('dealer.show');
Route::post('/dealer-{slug}/enquire', [\App\Http\Controllers\DealerController::class, 'submitEnquiry'])->name('dealer.enquire');
Route::get('/dealer-{slug}/vehicles', [\App\Http\Controllers\DealerController::class, 'getVehicles'])->name('dealer.vehicles');


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
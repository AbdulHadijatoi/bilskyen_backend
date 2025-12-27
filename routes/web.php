<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DmrTestController;

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

// Authenticated Routes - Require login
Route::middleware('auth.web')->group(function () {
    // Profile Routes
    Route::get('/profile', [HomeController::class, 'showProfile'])->name('profile');
    Route::post('/profile', [HomeController::class, 'updateProfile'])->name('profile.update');
    
    // Sell Your Car Routes
    Route::get('/sell-your-car', [\App\Http\Controllers\SellYourCarController::class, 'show'])->name('sell-your-car');
    Route::post('/sell-your-car', [\App\Http\Controllers\SellYourCarController::class, 'store'])->name('sell-your-car.store');
});

// About Page
Route::get('/about', [HomeController::class, 'showAbout'])->name('about');

// Contact Page
Route::get('/contact', [HomeController::class, 'showContact'])->name('contact');

// Vehicles Page
Route::get('/vehicles', [HomeController::class, 'showVehicles'])->name('vehicles');

// Vehicle Details Page
Route::get('/vehicles/{serialNo}', [HomeController::class, 'showVehicleDetail'])->name('vehicle.detail');


// Route::get('/test-mail', function () {
//     Mail::raw('Hello from Laravel + Gmail SMTP', function ($msg) {
//         $msg->to('abdulhadixt@gmail.com')
//             ->subject('Test Gmail SMTP');
//     });

//     return 'Mail sent';
// });


Route::prefix('test/dmr')->group(function () {
    Route::get('/webservice', [DmrTestController::class, 'dmrWebservice']);
    Route::get('/dataset', [DmrTestController::class, 'motorRegisterData']);
    Route::get('/scraper', [DmrTestController::class, 'jsDkCarScraper']);
    Route::get('/xmlstream', [DmrTestController::class, 'motorregisterXmlStream']);
});
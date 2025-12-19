<?php

use Illuminate\Support\Facades\Route;

// Home Page
Route::get('/', function () {
    return view('home');
})->name('home');

// Auth Routes
Route::prefix('auth')->group(function () {
    // Login
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');
    
    // Signup
    Route::get('/signup', function () {
        return view('auth.signup');
    })->name('signup');
    
    // Forgot Password
    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');
    
    // Reset Password
    Route::get('/reset-password', function () {
        $token = request()->query('token');
        $error = request()->query('error');
        return view('auth.reset-password', [
            'token' => $token,
            'error' => $error
        ]);
    })->name('password.reset');
    
    // Verify Email
    Route::get('/verify-email', function () {
        return view('auth.verify-email');
    })->name('verification.notice');
    
    // Magic Link Routes
    Route::prefix('magic-link')->group(function () {
        Route::get('/login', function () {
            return view('auth.magic-link.login');
        })->name('magic-link.login');
        
        Route::get('/signup', function () {
            return view('auth.magic-link.signup');
        })->name('magic-link.signup');
        
        Route::get('/verify', function () {
            $token = request()->query('token');
            $callbackURL = request()->query('callbackURL', '/');
            return view('auth.magic-link.verify', [
                'token' => $token,
                'callbackURL' => $callbackURL
            ]);
        })->name('magic-link.verify');
    });
});

// Profile Route
Route::get('/profile', function () {
    return view('profile');
})->name('profile');

// About Page
Route::get('/about', function () {
    return view('about');
})->name('about');

// Contact Page
Route::get('/contact', function () {
    return view('contact');
})->name('contact');

// Vehicles Page
Route::get('/vehicles', function () {
    return view('vehicles');
})->name('vehicles');

// Vehicle Details Page
Route::get('/vehicles/{serialNo}', function ($serialNo) {
    return view('vehicle-detail', ['serialNo' => $serialNo]);
})->name('vehicle.detail');

// Dealer Routes
Route::prefix('dealer')->group(function () {
    Route::get('/', function () {
        return view('dealer.index');
    })->name('dealer.dashboard');
    
    // Placeholder routes for all dealer sections
    Route::get('/vehicles', function () {
        return view('dealer.index'); // Will be replaced with actual views later
    });
    Route::get('/purchases', function () {
        return view('dealer.index');
    });
    Route::get('/sales', function () {
        return view('dealer.index');
    });
    Route::get('/expenses', function () {
        return view('dealer.index');
    });
    Route::get('/contacts', function () {
        return view('dealer.index');
    });
    Route::get('/enquiries', function () {
        return view('dealer.index');
    });
    Route::get('/accounting/transactions', function () {
        return view('dealer.index');
    });
    Route::get('/accounting/financial-accounts', function () {
        return view('dealer.index');
    });
    Route::get('/accounting/financial-reports', function () {
        return view('dealer.index');
    });
    Route::get('/settings', function () {
        return view('dealer.index');
    });
    Route::get('/notifications', function () {
        return view('dealer.index');
    });
});

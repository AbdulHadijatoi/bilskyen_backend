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
    
    // Vehicles
    Route::get('/vehicles', function () {
        return redirect('/dealer/vehicles/overview');
    })->name('dealer.vehicles');
    
    Route::get('/vehicles/overview', function () {
        return view('dealer.vehicles.overview');
    })->name('dealer.vehicles.overview');
    
    Route::get('/vehicles/add-vehicle', function () {
        return view('dealer.vehicles.add-vehicle');
    })->name('dealer.vehicles.add');
    
    // Purchases
    Route::get('/purchases', function () {
        return view('dealer.purchases');
    })->name('dealer.purchases');
    
    // Sales
    Route::get('/sales', function () {
        return view('dealer.sales');
    })->name('dealer.sales');
    
    // Expenses
    Route::get('/expenses', function () {
        return view('dealer.expenses');
    })->name('dealer.expenses');
    
    // Contacts
    Route::get('/contacts', function () {
        return view('dealer.contacts');
    })->name('dealer.contacts');
    
    // Enquiries
    Route::get('/enquiries', function () {
        return view('dealer.enquiries');
    })->name('dealer.enquiries');
    
    // Accounting
    Route::prefix('accounting')->group(function () {
        Route::get('/transactions', function () {
            return view('dealer.accounting.transactions');
        })->name('dealer.accounting.transactions');
        
        Route::get('/financial-accounts', function () {
            return view('dealer.accounting.financial-accounts');
        })->name('dealer.accounting.financial-accounts');
        
        Route::get('/financial-reports', function () {
            return view('dealer.accounting.financial-reports');
        })->name('dealer.accounting.financial-reports');
    });
    
    // Settings
    Route::get('/settings', function () {
        return view('dealer.settings');
    })->name('dealer.settings');
    
    // Notifications
    Route::get('/notifications', function () {
        return view('dealer.index'); // Placeholder
    })->name('dealer.notifications');
});

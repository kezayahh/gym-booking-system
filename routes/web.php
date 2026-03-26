<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AdminAuthController;

$spaView = function () {
    return view('app');
};

// ============================================
// USER ROUTES
// ============================================
Route::prefix('user')->group(function () use ($spaView) {
    // Auth pages
    Route::get('/login', $spaView)->name('user.login');
    Route::get('/register', $spaView)->name('user.register');

    // Auth actions
    Route::post('/login', [AuthController::class, 'login'])->name('user.login.submit');
    Route::post('/register', [AuthController::class, 'register'])->name('user.register.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('user.logout');

    // SPA pages
    Route::get('/dashboard', $spaView)->name('user.dashboard');
    Route::get('/schedule', $spaView)->name('user.schedule');
    Route::get('/bookings', $spaView)->name('user.bookings');
    Route::get('/payments', $spaView)->name('user.payments');
    Route::get('/notifications', $spaView)->name('user.notifications');
    Route::get('/profile', $spaView)->name('user.profile');
});

// ============================================
// ADMIN ROUTES
// ============================================
Route::prefix('admin')->group(function () use ($spaView) {
    // Auth page
    Route::get('/login', $spaView)->name('admin.login');

    // Auth actions
    Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // SPA pages
    Route::get('/dashboard', $spaView)->name('admin.dashboard');
    Route::get('/users', $spaView)->name('admin.users');
    Route::get('/schedules', $spaView)->name('admin.schedules');
    Route::get('/bookings', $spaView)->name('admin.bookings');
    Route::get('/payments', $spaView)->name('admin.payments');
    Route::get('/refunds', $spaView)->name('admin.refunds');
    Route::get('/reports', $spaView)->name('admin.reports');
    Route::get('/profile', $spaView)->name('admin.profile');
});

// ============================================
// ROOT
// ============================================
Route::get('/', function () {
    return redirect('/user/login');
});

// ============================================
// OPTIONAL SPA FALLBACK
// ============================================
Route::get('/{any}', $spaView)->where('any', '^(?!api|storage|sanctum).*$');
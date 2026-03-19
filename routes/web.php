<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AdminAuthController;

Route::prefix('user')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::view('/login', 'app')->name('login');
        Route::view('/register', 'app')->name('register');

        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
        Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    });

    Route::middleware(['auth', 'role:user'])->group(function () {
        Route::view('/dashboard', 'app')->name('user.dashboard');
        Route::view('/schedule', 'app')->name('user.schedule');
        Route::view('/bookings', 'app')->name('user.bookings');
        Route::view('/payments', 'app')->name('user.payments');
        Route::view('/notifications', 'app')->name('user.notifications');
        Route::view('/profile', 'app')->name('user.profile');
    });
});

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::view('/login', 'app')->name('admin.login');

        Route::post('/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::view('/dashboard', 'app')->name('admin.dashboard');
        Route::view('/users', 'app')->name('admin.users');
        Route::view('/schedules', 'app')->name('admin.schedules');
        Route::view('/bookings', 'app')->name('admin.bookings');
        Route::view('/payments', 'app')->name('admin.payments');
        Route::view('/refunds', 'app')->name('admin.refunds');
        Route::view('/reports', 'app')->name('admin.reports');
        Route::view('/profile', 'app')->name('admin.profile');
    });
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
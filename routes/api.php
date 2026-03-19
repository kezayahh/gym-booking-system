<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\ScheduleController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\ProfileController;

Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/user/dashboard', [DashboardController::class, 'index']);

    Route::get('/user/schedule', [ScheduleController::class, 'index']);
    Route::get('/user/schedule/date', [ScheduleController::class, 'getSchedulesByDate']);
    Route::get('/user/schedule/{id}', [ScheduleController::class, 'show']);

    Route::get('/user/bookings', [BookingController::class, 'index']);
    Route::post('/user/bookings/store', [BookingController::class, 'store']);
    Route::post('/user/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::get('/user/bookings/{booking}/details', [BookingController::class, 'details']);

    Route::get('/user/payments', [PaymentController::class, 'index']);
    Route::post('/user/payments/{id}/process', [PaymentController::class, 'processPayment']);
    Route::post('/user/payments/{id}/refund', [PaymentController::class, 'requestRefund']);

    Route::get('/user/notifications', [NotificationController::class, 'index']);
    Route::post('/user/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/user/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/user/notifications/{id}/delete', [NotificationController::class, 'delete']);
    Route::post('/user/notifications/delete-all', [NotificationController::class, 'deleteAll']);

    Route::get('/user/profile', [ProfileController::class, 'index']);
    Route::post('/user/profile/update-info', [ProfileController::class, 'updateInfo']);
    Route::post('/user/profile/update-password', [ProfileController::class, 'updatePassword']);
    Route::post('/user/profile/upload-photo', [ProfileController::class, 'uploadPhoto']);
    Route::post('/user/profile/delete-photo', [ProfileController::class, 'deletePhoto']);
});
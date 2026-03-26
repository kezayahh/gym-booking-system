<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\ScheduleController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\PaymentController;
use App\Http\Controllers\User\NotificationController;
use App\Http\Controllers\User\ProfileController;

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ScheduleManagementController;
use App\Http\Controllers\Admin\BookingManagementController;
use App\Http\Controllers\Admin\PaymentManagementController;
use App\Http\Controllers\Admin\RefundManagementController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Auth\AdminAuthController;

// -----------------------------
// USER API ROUTES
// -----------------------------
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

// -----------------------------
// ADMIN AUTH CHECK
// -----------------------------
Route::middleware(['auth', 'role:admin'])->get('/admin/me', [AdminAuthController::class, 'me']);

// -----------------------------
// ADMIN API ROUTES
// -----------------------------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboardData']);

    // special routes first
    Route::get('/users/active', [BookingManagementController::class, 'activeUsers']);
    Route::get('/schedules/available', [BookingManagementController::class, 'availableSchedules']);
    Route::get('/bookings/stats', [BookingManagementController::class, 'stats']);
    Route::get('/payments/stats', [PaymentManagementController::class, 'stats']);
    Route::get('/payments/method-stats', [PaymentManagementController::class, 'methodStats']);
    Route::get('/payments/export', [PaymentManagementController::class, 'exportReport']);

    // users
    Route::get('/users', [UserManagementController::class, 'getUsersData']);
    Route::post('/users', [UserManagementController::class, 'store']);
    Route::put('/users/{id}', [UserManagementController::class, 'update']);
    Route::patch('/users/{id}/toggle-status', [UserManagementController::class, 'toggleStatus']);
    Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);
    Route::get('/users/{id}', [UserManagementController::class, 'show']);

    // schedules
    Route::get('/schedules', [ScheduleManagementController::class, 'index']);
    Route::post('/schedules', [ScheduleManagementController::class, 'store']);
    Route::post('/schedules/bulk-create', [ScheduleManagementController::class, 'bulkCreate']);
    Route::put('/schedules/{id}', [ScheduleManagementController::class, 'update']);
    Route::delete('/schedules/{id}', [ScheduleManagementController::class, 'destroy']);
    Route::get('/schedules/{id}', [ScheduleManagementController::class, 'show']);

    // bookings
    Route::get('/bookings', [BookingManagementController::class, 'index']);
    Route::post('/bookings', [BookingManagementController::class, 'store']);
    Route::post('/bookings/{id}/confirm', [BookingManagementController::class, 'confirm']);
    Route::post('/bookings/{id}/complete', [BookingManagementController::class, 'complete']);
    Route::post('/bookings/{id}/cancel', [BookingManagementController::class, 'cancel']);
    Route::delete('/bookings/{id}', [BookingManagementController::class, 'destroy']);
    Route::get('/bookings/{id}', [BookingManagementController::class, 'show']);

    // payments
    Route::get('/payments', [PaymentManagementController::class, 'index']);
    Route::get('/payments/{id}', [PaymentManagementController::class, 'show']);
    Route::post('/payments/{id}/verify', [PaymentManagementController::class, 'verify']);
    Route::post('/payments/{id}/mark-failed', [PaymentManagementController::class, 'markAsFailed']);
    Route::post('/payments/{id}/update', [PaymentManagementController::class, 'update']);
    Route::post('/payments/{id}/delete', [PaymentManagementController::class, 'destroy']);

    // refunds
    Route::get('/refunds/stats', [RefundManagementController::class, 'stats']);
    Route::get('/refunds/export', [RefundManagementController::class, 'exportReport']);
    Route::get('/refunds', [RefundManagementController::class, 'index']);
    Route::get('/refunds/{id}', [RefundManagementController::class, 'show']);
    Route::post('/refunds/{id}/approve', [RefundManagementController::class, 'approve']);
    Route::post('/refunds/{id}/reject', [RefundManagementController::class, 'reject']);
    Route::post('/refunds/{id}/complete', [RefundManagementController::class, 'complete']);
    Route::post('/refunds/{id}/update', [RefundManagementController::class, 'update']);
    Route::post('/refunds/{id}/delete', [RefundManagementController::class, 'destroy']);

    // reports
    Route::get('/reports', [ReportsController::class, 'index']);
    Route::get('/reports/export-csv', [ReportsController::class, 'exportCSV']);

    // profile
    Route::get('/profile', [AdminProfileController::class, 'index']);
    Route::post('/profile/update-info', [AdminProfileController::class, 'updateInfo']);
    Route::post('/profile/update-password', [AdminProfileController::class, 'updatePassword']);
    Route::post('/profile/upload-photo', [AdminProfileController::class, 'uploadPhoto']);
    Route::post('/profile/delete-photo', [AdminProfileController::class, 'deletePhoto']);
});
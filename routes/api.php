<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\PasswordController;
use App\Http\Controllers\Api\Feed\ArchiveController;
use App\Http\Controllers\Api\Feed\FeedController;
use App\Http\Controllers\Api\Location\LocationController;
use App\Http\Controllers\Api\Notification\NotificationController;
use App\Http\Controllers\Api\Profile\ProfileController;
use App\Http\Controllers\Api\Submission\SubmissionController;
use App\Http\Controllers\Api\Subscription\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('register')->group(function (): void {
    Route::post('/', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
    Route::post('resend-otp', [AuthController::class, 'resendOtp'])->middleware('throttle:5,1');
});

Route::prefix('login')->group(function (): void {
    Route::post('/', [AuthController::class, 'login']);
});

Route::post('forgot-password', [PasswordController::class, 'sendResetLink']);
Route::post('reset-password', [PasswordController::class, 'reset']);

Route::get('locations/countries', [LocationController::class, 'countries']);
Route::get('locations/countries/{country}/regions', [LocationController::class, 'regions']);
Route::get('locations/regions/{region}/counties', [LocationController::class, 'counties']);
Route::post('locations/auto-detect', [LocationController::class, 'autoDetect']);

Route::get('feed', [FeedController::class, 'index']);
Route::get('feed/counties', [FeedController::class, 'availableCounties']);
Route::get('feed/{post}', [FeedController::class, 'show']);
Route::get('archive', [ArchiveController::class, 'index']);

Route::middleware(['auth:api', 'active'])->group(function (): void {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::put('profile', [ProfileController::class, 'update']);
    Route::delete('profile', [ProfileController::class, 'destroy']);
    Route::post('user/location', [LocationController::class, 'storeUserLocation']);
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/device-token', [NotificationController::class, 'storeDeviceToken']);
    Route::delete('notifications/device-token', [NotificationController::class, 'destroyDeviceToken']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

    Route::post('archive/purchase', [ArchiveController::class, 'purchase']);
    Route::get('purchases', [ArchiveController::class, 'history']);

    Route::get('subscription/status', [SubscriptionController::class, 'status']);
    Route::post('subscription/verify', [SubscriptionController::class, 'verify']);

    Route::middleware('subscribed')->group(function (): void {
        Route::post('submissions', [SubmissionController::class, 'store']);
    });

    Route::get('submissions/my', [SubmissionController::class, 'index']);
});

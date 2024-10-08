<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WordController;
use App\Http\Controllers\WordDetailController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HistoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'Fullstack Challenge 🏅 - Dictionary';
});

Route::middleware(['guest'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/signup', [RegisteredUserController::class, 'store'])
            ->name('register');

        Route::post('/signin', [AuthenticatedSessionController::class, 'store'])
            ->name('login');

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/me', [UserController::class, 'me'])
            ->name('profile');
        Route::get('/me/favorites', [FavoriteController::class, 'index'])
            ->name('favorites');
        Route::get('/me/history', [HistoryController::class, 'index'])
            ->name('history');
    });

    Route::prefix('entries')->group(function () {
        Route::get('/en', [WordController::class, 'index'])
            ->name('index');
        Route::get('/en/{word}', [WordDetailController::class, 'show'])
            ->name('show');
        Route::post('/en/{word}/favorite', [FavoriteController::class, 'store'])
            ->name('favorite');
        Route::delete('/en/{word}/unfavorite', [FavoriteController::class, 'destroy'])
            ->name('unfavorite');
    });
});

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

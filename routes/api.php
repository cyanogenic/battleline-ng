<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/register', [RegisteredUserController::class, 'store'])->name('auth.register');
    Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', CurrentUserController::class)->name('me');
        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');
        Route::post('/auth/logout-all', [AuthenticatedSessionController::class, 'destroyAll'])->name('auth.logout-all');
    });
});

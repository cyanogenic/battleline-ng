<?php

use App\Http\Controllers\Api\V1\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\V1\Auth\RegisteredUserController;
use App\Http\Controllers\Api\V1\BattleLineGameController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\LobbyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:register')
        ->name('auth.register');
    Route::post('/auth/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', CurrentUserController::class)->name('me');
        Route::get('/lobby', LobbyController::class)
            ->middleware('throttle:battle-line-state')
            ->name('lobby');
        Route::post('/games', [BattleLineGameController::class, 'store'])->name('games.store');
        Route::get('/games/{battleLineGame}', [BattleLineGameController::class, 'show'])
            ->middleware('throttle:battle-line-state')
            ->name('games.show');
        Route::post('/games/{battleLineGame}/join', [BattleLineGameController::class, 'join'])->name('games.join');
        Route::post('/games/{battleLineGame}/actions', [BattleLineGameController::class, 'executeAction'])
            ->middleware('throttle:battle-line-actions')
            ->name('games.actions.store');
        Route::post('/auth/logout', [AuthenticatedSessionController::class, 'destroy'])->name('auth.logout');
        Route::post('/auth/logout-all', [AuthenticatedSessionController::class, 'destroyAll'])->name('auth.logout-all');
    });
});

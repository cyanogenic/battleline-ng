<?php

use App\Http\Controllers\Api\BattleLineGameController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BattleLineGamePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BattleLineGamePageController::class, 'index'])->name('battle-line-games.page.index');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:register');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/battle-line-games', [BattleLineGamePageController::class, 'store'])->name('battle-line-games.store');
    Route::post('/battle-line-games/{battleLineGame}/join', [BattleLineGamePageController::class, 'join'])->name('battle-line-games.join');
    Route::get('/battle-line-games/{battleLineGame}', [BattleLineGamePageController::class, 'show'])->name('battle-line-games.page.show');
    Route::get('/battle-line-games/{battleLineGame}/state', [BattleLineGameController::class, 'show'])
        ->middleware('throttle:battle-line-state')
        ->name('battle-line-games.show');
    Route::post('/battle-line-games/{battleLineGame}/actions', [BattleLineGameController::class, 'executeAction'])
        ->middleware('throttle:battle-line-actions')
        ->name('battle-line-games.actions.store');
});

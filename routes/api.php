<?php

use App\Http\Controllers\Api\BattleLineGameController;
use Illuminate\Support\Facades\Route;

Route::prefix('battle-line-games')->group(function (): void {
    Route::post('/', [BattleLineGameController::class, 'store'])->name('battle-line-games.store');
    Route::get('/{battleLineGame}', [BattleLineGameController::class, 'show'])->name('battle-line-games.show');
    Route::post('/{battleLineGame}/actions', [BattleLineGameController::class, 'executeAction'])->name('battle-line-games.actions.store');
});

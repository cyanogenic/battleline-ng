<?php

use App\Http\Controllers\BattleLineGamePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BattleLineGamePageController::class, 'index'])->name('battle-line-games.page.index');
Route::get('/battle-line-games/{battleLineGame}', [BattleLineGamePageController::class, 'show'])->name('battle-line-games.page.show');

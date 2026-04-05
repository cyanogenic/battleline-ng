<?php

namespace App\Http\Controllers;

use App\Models\BattleLineGame;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BattleLineGamePageController extends Controller
{
    public function index(): View
    {
        return view('battle-line.index', [
            'recentGames' => BattleLineGame::query()
                ->latest()
                ->take(6)
                ->get(),
        ]);
    }

    public function show(Request $request, BattleLineGame $battleLineGame): View
    {
        $viewerOptions = [
            $battleLineGame->player_one_name,
            $battleLineGame->player_two_name,
        ];

        $viewerPlayerId = in_array($request->string('viewer_player_id')->toString(), $viewerOptions, true)
            ? $request->string('viewer_player_id')->toString()
            : $battleLineGame->player_one_name;

        return view('battle-line.show', [
            'game' => $battleLineGame,
            'viewerPlayerId' => $viewerPlayerId,
            'viewerOptions' => $viewerOptions,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\BattleLine\CreateBattleLineGameAction;
use App\Actions\BattleLine\JoinBattleLineGameAction;
use App\Http\Requests\JoinBattleLineGameRequest;
use App\Http\Requests\StoreBattleLineGameRequest;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BattleLineGamePageController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User|null $user */
        $user = $request->user();
        $myGames = collect();
        $joinableGames = collect();
        $openGame = null;

        if ($user !== null) {
            $openGame = BattleLineGame::query()
                ->openForUser($user)
                ->latest('updated_at')
                ->first();

            $myGames = BattleLineGame::query()
                ->forUser($user)
                ->latest()
                ->take(6)
                ->get();

            $joinableGames = BattleLineGame::query()
                ->joinableFor($user)
                ->latest()
                ->take(6)
                ->get();
        }

        return view('battle-line.index', [
            'myGames' => $myGames,
            'joinableGames' => $joinableGames,
            'openGame' => $openGame,
        ]);
    }

    public function store(StoreBattleLineGameRequest $request, CreateBattleLineGameAction $createGame): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $game = $createGame->execute($user);

        return redirect()->route('battle-line-games.page.show', $game);
    }

    public function join(
        JoinBattleLineGameRequest $request,
        BattleLineGame $battleLineGame,
        JoinBattleLineGameAction $joinGame,
    ): RedirectResponse {
        /** @var User $joiningUser */
        $joiningUser = $request->user();
        $game = $joinGame->execute($battleLineGame, $joiningUser);

        return redirect()->route('battle-line-games.page.show', $game);
    }

    public function show(Request $request, BattleLineGame $battleLineGame): View
    {
        $this->authorize('view', $battleLineGame);

        return view('battle-line.show', [
            'game' => $battleLineGame,
            'viewerPlayerId' => $battleLineGame->seatFor($request->user()),
        ]);
    }
}

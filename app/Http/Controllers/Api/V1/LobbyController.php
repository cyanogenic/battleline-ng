<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BattleLineGameSummaryResource;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LobbyController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

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

        return response()->json([
            'data' => [
                'open_game' => $openGame === null
                    ? null
                    : BattleLineGameSummaryResource::make($openGame)->resolve($request),
                'my_games' => BattleLineGameSummaryResource::collection($myGames)->resolve($request),
                'joinable_games' => BattleLineGameSummaryResource::collection($joinableGames)->resolve($request),
            ],
        ]);
    }
}

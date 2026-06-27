<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BattleLine\CreateBattleLineGameAction;
use App\Actions\BattleLine\JoinBattleLineGameAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinBattleLineGameRequest;
use App\Http\Requests\StoreBattleLineGameRequest;
use App\Http\Resources\Api\V1\BattleLineGameSummaryResource;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class BattleLineGameController extends Controller
{
    public function store(StoreBattleLineGameRequest $request, CreateBattleLineGameAction $createGame): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $game = $createGame->execute($user);

        return response()->json([
            'data' => BattleLineGameSummaryResource::make($game)->resolve($request),
        ], 201);
    }

    public function join(
        JoinBattleLineGameRequest $request,
        BattleLineGame $battleLineGame,
        JoinBattleLineGameAction $joinGame,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $game = $joinGame->execute($battleLineGame, $user);

        return response()->json([
            'data' => BattleLineGameSummaryResource::make($game)->resolve($request),
        ]);
    }
}

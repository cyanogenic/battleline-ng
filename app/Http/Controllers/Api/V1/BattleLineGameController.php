<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\BattleLine\CreateBattleLineGameAction;
use App\Actions\BattleLine\JoinBattleLineGameAction;
use App\Domain\Game\Support\GameStateViewProjector;
use App\Http\Controllers\Controller;
use App\Http\Requests\JoinBattleLineGameRequest;
use App\Http\Requests\ShowBattleLineGameRequest;
use App\Http\Requests\StoreBattleLineGameRequest;
use App\Http\Resources\Api\V1\BattleLineGameResource;
use App\Http\Resources\Api\V1\BattleLineGameSummaryResource;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class BattleLineGameController extends Controller
{
    public function __construct(
        private readonly GameStateViewProjector $projector = new GameStateViewProjector,
    ) {}

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

    public function show(ShowBattleLineGameRequest $request, BattleLineGame $battleLineGame): BattleLineGameResource
    {
        /** @var User $user */
        $user = $request->user();

        return new BattleLineGameResource(
            resource: $battleLineGame->loadMissing(['playerOneUser', 'playerTwoUser', 'winnerUser']),
            viewerPlayerId: $battleLineGame->seatFor($user),
            projector: $this->projector,
        );
    }
}

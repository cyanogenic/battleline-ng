<?php

namespace App\Http\Controllers\Api;

use App\Actions\BattleLine\ExecuteBattleLineAction;
use App\Domain\Game\Support\GameStateViewProjector;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExecuteBattleLineActionRequest;
use App\Http\Requests\ShowBattleLineGameRequest;
use App\Http\Resources\BattleLineGameResource;
use App\Models\BattleLineGame;
use App\Models\User;

class BattleLineGameController extends Controller
{
    public function __construct(
        private readonly GameStateViewProjector $projector = new GameStateViewProjector,
    ) {}

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

    public function executeAction(
        ExecuteBattleLineActionRequest $request,
        BattleLineGame $battleLineGame,
        ExecuteBattleLineAction $executeAction,
    ): BattleLineGameResource {
        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();
        $viewerPlayerId = $battleLineGame->seatFor($user);
        $updatedGame = $executeAction->execute($battleLineGame, $user, $validated);

        return new BattleLineGameResource(
            resource: $updatedGame->loadMissing(['playerOneUser', 'playerTwoUser', 'winnerUser']),
            viewerPlayerId: $viewerPlayerId,
            projector: $this->projector,
        );
    }
}

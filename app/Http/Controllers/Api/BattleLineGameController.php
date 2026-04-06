<?php

namespace App\Http\Controllers\Api;

use App\Domain\Game\Exceptions\InvalidGameAction;
use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\Support\GameStateViewProjector;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExecuteBattleLineActionRequest;
use App\Http\Requests\ShowBattleLineGameRequest;
use App\Http\Resources\BattleLineGameResource;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BattleLineGameController extends Controller
{
    public function __construct(
        private readonly BattleLineEngine $engine = new BattleLineEngine,
        private readonly GameStateSerializer $serializer = new GameStateSerializer,
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

    /**
     * @throws ValidationException
     */
    public function executeAction(ExecuteBattleLineActionRequest $request, BattleLineGame $battleLineGame): BattleLineGameResource|JsonResponse
    {
        $validated = $request->validated();
        /** @var User $user */
        $user = $request->user();
        $viewerPlayerId = $battleLineGame->seatFor($user);

        if ($viewerPlayerId === null) {
            throw ValidationException::withMessages([
                'player' => ['The authenticated user is not part of this battle.'],
            ]);
        }

        $state = $this->serializer->deserialize($battleLineGame->state);

        try {
            $updatedState = match ($validated['type']) {
                'play_troop' => $this->engine->playTroopCard(
                    state: $state,
                    playerId: $viewerPlayerId,
                    cardId: $validated['card_id'],
                    flagIndex: $validated['flag_index'],
                ),
                'claim_flag' => $this->engine->claimFlag(
                    state: $state,
                    playerId: $viewerPlayerId,
                    flagIndex: $validated['flag_index'],
                ),
                'pass' => $this->engine->passTurn(
                    state: $state,
                    playerId: $viewerPlayerId,
                ),
                'finish_turn' => $this->engine->finishTurn(
                    state: $state,
                    playerId: $viewerPlayerId,
                ),
            };
        } catch (InvalidGameAction $exception) {
            throw ValidationException::withMessages([
                'action' => [$exception->getMessage()],
            ]);
        }

        $battleLineGame->forceFill([
            'status' => $updatedState->phase->value,
            'winner_user_id' => $updatedState->winnerId === null
                ? null
                : match ($updatedState->winnerId) {
                    BattleLineGame::PlayerOneSeat => $battleLineGame->player_one_user_id,
                    BattleLineGame::PlayerTwoSeat => $battleLineGame->player_two_user_id,
                    default => null,
                },
            'winner_name' => $updatedState->winnerId === null ? null : $battleLineGame->nameForSeat($updatedState->winnerId),
            'state' => $this->serializer->serialize($updatedState),
        ])->save();

        return new BattleLineGameResource(
            resource: $battleLineGame->refresh()->loadMissing(['playerOneUser', 'playerTwoUser', 'winnerUser']),
            viewerPlayerId: $viewerPlayerId,
            projector: $this->projector,
        );
    }
}

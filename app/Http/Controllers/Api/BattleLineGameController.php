<?php

namespace App\Http\Controllers\Api;

use App\Domain\Game\Exceptions\InvalidGameAction;
use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\Support\GameStateViewProjector;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExecuteBattleLineActionRequest;
use App\Http\Requests\ShowBattleLineGameRequest;
use App\Http\Requests\StoreBattleLineGameRequest;
use App\Http\Resources\BattleLineGameResource;
use App\Models\BattleLineGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BattleLineGameController extends Controller
{
    public function __construct(
        private readonly BattleLineEngine $engine = new BattleLineEngine,
        private readonly GameStateSerializer $serializer = new GameStateSerializer,
        private readonly GameStateViewProjector $projector = new GameStateViewProjector,
    ) {}

    public function store(StoreBattleLineGameRequest $request): BattleLineGameResource
    {
        $validated = $request->validated();
        $shuffledDeck = collect(TroopCard::standardDeck())->shuffle()->values()->all();
        $firstPlayerHand = array_slice($shuffledDeck, 0, 7);
        $secondPlayerHand = array_slice($shuffledDeck, 7, 7);
        $remainingDeck = array_values(array_slice($shuffledDeck, 14));
        $startingPlayerName = $validated['starting_player_name'] ?? $validated['player_one_name'];

        $state = $this->engine->startGame(
            firstPlayerId: $validated['player_one_name'],
            secondPlayerId: $validated['player_two_name'],
            firstPlayerHand: $firstPlayerHand,
            secondPlayerHand: $secondPlayerHand,
            troopDeck: $remainingDeck,
            startingPlayerId: $startingPlayerName,
        );

        $game = BattleLineGame::create([
            'player_one_name' => $validated['player_one_name'],
            'player_two_name' => $validated['player_two_name'],
            'status' => $state->phase->value,
            'winner_name' => $state->winnerId,
            'state' => $this->serializer->serialize($state),
        ]);

        return new BattleLineGameResource(
            resource: $game,
            viewerPlayerId: $validated['viewer_player_id'],
            projector: $this->projector,
        );
    }

    public function show(ShowBattleLineGameRequest $request, BattleLineGame $battleLineGame): BattleLineGameResource
    {
        return new BattleLineGameResource(
            resource: $battleLineGame,
            viewerPlayerId: $request->validated('viewer_player_id'),
            projector: $this->projector,
        );
    }

    /**
     * @throws ValidationException
     */
    public function executeAction(ExecuteBattleLineActionRequest $request, BattleLineGame $battleLineGame): BattleLineGameResource|JsonResponse
    {
        $validated = $request->validated();
        $this->ensurePlayerBelongsToGame($battleLineGame, $validated['player_id']);
        $state = $this->serializer->deserialize($battleLineGame->state);

        try {
            $updatedState = match ($validated['type']) {
                'play_troop' => $this->engine->playTroopCard(
                    state: $state,
                    playerId: $validated['player_id'],
                    cardId: $validated['card_id'],
                    flagIndex: $validated['flag_index'],
                ),
                'claim_flag' => $this->engine->claimFlag(
                    state: $state,
                    playerId: $validated['player_id'],
                    flagIndex: $validated['flag_index'],
                ),
                'pass' => $this->engine->passTurn(
                    state: $state,
                    playerId: $validated['player_id'],
                ),
                'finish_turn' => $this->engine->finishTurn(
                    state: $state,
                    playerId: $validated['player_id'],
                ),
            };
        } catch (InvalidGameAction $exception) {
            throw ValidationException::withMessages([
                'action' => [$exception->getMessage()],
            ]);
        }

        $battleLineGame->forceFill([
            'status' => $updatedState->phase->value,
            'winner_name' => $updatedState->winnerId,
            'state' => $this->serializer->serialize($updatedState),
        ])->save();

        return new BattleLineGameResource(
            resource: $battleLineGame->refresh(),
            viewerPlayerId: $validated['player_id'],
            projector: $this->projector,
        );
    }

    /**
     * @throws ValidationException
     */
    private function ensurePlayerBelongsToGame(BattleLineGame $battleLineGame, string $playerId): void
    {
        if (! in_array($playerId, [$battleLineGame->player_one_name, $battleLineGame->player_two_name], true)) {
            throw ValidationException::withMessages([
                'player_id' => ['The specified player does not belong to this game.'],
            ]);
        }
    }
}

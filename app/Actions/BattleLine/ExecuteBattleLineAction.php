<?php

namespace App\Actions\BattleLine;

use App\Domain\Game\Exceptions\InvalidGameAction;
use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ExecuteBattleLineAction
{
    public function __construct(
        private readonly BattleLineEngine $engine = new BattleLineEngine,
        private readonly GameStateSerializer $serializer = new GameStateSerializer,
    ) {}

    /**
     * @param  array{type: string, state_version: int, card_id?: string, flag_index?: int}  $validated
     *
     * @throws ValidationException
     */
    public function execute(BattleLineGame $battleLineGame, User $user, array $validated): BattleLineGame
    {
        return DB::transaction(function () use ($battleLineGame, $user, $validated): BattleLineGame {
            /** @var BattleLineGame $lockedGame */
            $lockedGame = BattleLineGame::query()->lockForUpdate()->findOrFail($battleLineGame->getKey());
            $viewerPlayerId = $lockedGame->seatFor($user);

            if ($viewerPlayerId === null) {
                throw ValidationException::withMessages([
                    'player' => ['The authenticated user is not part of this battle.'],
                ]);
            }

            if ($validated['state_version'] !== $lockedGame->state_version) {
                throw new ConflictHttpException('The battle state is outdated. Refresh and try again.');
            }

            $state = $this->serializer->deserialize($lockedGame->state);

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

            $lockedGame->forceFill([
                'status' => $updatedState->phase->value,
                'state_version' => $lockedGame->state_version + 1,
                'winner_user_id' => $updatedState->winnerId === null
                    ? null
                    : match ($updatedState->winnerId) {
                        BattleLineGame::PlayerOneSeat => $lockedGame->player_one_user_id,
                        BattleLineGame::PlayerTwoSeat => $lockedGame->player_two_user_id,
                        default => null,
                    },
                'winner_name' => $updatedState->winnerId === null ? null : $lockedGame->nameForSeat($updatedState->winnerId),
                'state' => $this->serializer->serialize($updatedState),
            ])->save();

            return $lockedGame->refresh();
        }, attempts: 5);
    }
}

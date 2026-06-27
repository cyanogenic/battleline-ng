<?php

namespace App\Actions\BattleLine;

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JoinBattleLineGameAction
{
    public function __construct(
        private readonly BattleLineEngine $engine = new BattleLineEngine,
        private readonly GameStateSerializer $serializer = new GameStateSerializer,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(BattleLineGame $battleLineGame, User $joiningUser): BattleLineGame
    {
        return DB::transaction(function () use ($battleLineGame, $joiningUser): BattleLineGame {
            /** @var BattleLineGame $lockedGame */
            $lockedGame = BattleLineGame::query()->lockForUpdate()->findOrFail($battleLineGame->getKey());

            if (BattleLineGame::query()->openForUser($joiningUser)->exists()) {
                throw ValidationException::withMessages([
                    'game' => ['You already have an open battle. Finish it before joining another one.'],
                ]);
            }

            if (! $lockedGame->canBeJoinedBy($joiningUser)) {
                throw ValidationException::withMessages([
                    'game' => ['This battle is no longer available to join.'],
                ]);
            }

            $shuffledDeck = collect(TroopCard::standardDeck())->shuffle()->values()->all();
            $firstPlayerHand = array_slice($shuffledDeck, 0, 7);
            $secondPlayerHand = array_slice($shuffledDeck, 7, 7);
            $remainingDeck = array_values(array_slice($shuffledDeck, 14));
            $startingPlayerId = random_int(0, 1) === 0
                ? BattleLineGame::PlayerOneSeat
                : BattleLineGame::PlayerTwoSeat;

            $state = $this->engine->startGame(
                firstPlayerId: BattleLineGame::PlayerOneSeat,
                secondPlayerId: BattleLineGame::PlayerTwoSeat,
                firstPlayerHand: $firstPlayerHand,
                secondPlayerHand: $secondPlayerHand,
                troopDeck: $remainingDeck,
                startingPlayerId: $startingPlayerId,
            );

            $lockedGame->forceFill([
                'player_two_user_id' => $joiningUser->getKey(),
                'player_two_name' => $joiningUser->name,
                'status' => $state->phase->value,
                'state_version' => $lockedGame->state_version + 1,
                'state' => $this->serializer->serialize($state),
            ])->save();

            return $lockedGame->refresh();
        }, attempts: 5);
    }
}

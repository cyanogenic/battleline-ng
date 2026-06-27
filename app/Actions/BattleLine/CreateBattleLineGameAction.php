<?php

namespace App\Actions\BattleLine;

use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateBattleLineGameAction
{
    /**
     * @throws ValidationException
     */
    public function execute(User $user): BattleLineGame
    {
        if (BattleLineGame::query()->openForUser($user)->exists()) {
            throw ValidationException::withMessages([
                'game' => ['You already have an open battle. Finish it before creating a new one.'],
            ]);
        }

        return BattleLineGame::query()->create([
            'player_one_user_id' => $user->getKey(),
            'player_one_name' => $user->name,
            'player_two_name' => 'Awaiting challenger',
            'status' => BattleLineGame::WaitingForOpponentStatus,
            'state' => [],
        ]);
    }
}

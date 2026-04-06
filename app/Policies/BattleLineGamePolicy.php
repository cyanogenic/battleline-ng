<?php

namespace App\Policies;

use App\Models\BattleLineGame;
use App\Models\User;

class BattleLineGamePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BattleLineGame $battleLineGame): bool
    {
        return $battleLineGame->isParticipant($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function join(User $user, BattleLineGame $battleLineGame): bool
    {
        return $battleLineGame->canBeJoinedBy($user);
    }

    public function act(User $user, BattleLineGame $battleLineGame): bool
    {
        return $battleLineGame->isParticipant($user) && $battleLineGame->hasStarted();
    }
}

<?php

namespace App\Domain\Game\Evaluators;

use App\Domain\Game\Entities\GameState;

final class VictoryEvaluator
{
    public function winner(GameState $state): ?string
    {
        foreach ($state->playerOrder as $playerId) {
            $claimedFlags = $state->player($playerId)->claimedFlags;

            if (count($claimedFlags) >= 5) {
                return $playerId;
            }

            if ($this->hasThreeAdjacentFlags($claimedFlags)) {
                return $playerId;
            }
        }

        return null;
    }

    /**
     * @param  array<int, int>  $claimedFlags
     */
    private function hasThreeAdjacentFlags(array $claimedFlags): bool
    {
        sort($claimedFlags);

        for ($index = 0; $index <= count($claimedFlags) - 3; $index++) {
            if (
                $claimedFlags[$index + 1] === $claimedFlags[$index] + 1
                && $claimedFlags[$index + 2] === $claimedFlags[$index] + 2
            ) {
                return true;
            }
        }

        return false;
    }
}

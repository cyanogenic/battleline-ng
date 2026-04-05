<?php

namespace App\Domain\Game\Evaluators;

use App\Domain\Game\Enums\FormationRank;
use App\Domain\Game\ValueObjects\Formation;
use App\Domain\Game\ValueObjects\PlacedTroopCard;
use App\Domain\Game\ValueObjects\TroopCard;
use InvalidArgumentException;

final class FormationEvaluator
{
    /**
     * @param  array<int, TroopCard|PlacedTroopCard>  $cards
     */
    public function evaluate(array $cards): Formation
    {
        $troopCards = array_map(
            fn (TroopCard|PlacedTroopCard $card): TroopCard => $card instanceof PlacedTroopCard ? $card->card : $card,
            $cards,
        );

        if (count($troopCards) !== 3) {
            throw new InvalidArgumentException('A base formation must contain exactly three troop cards.');
        }

        $colors = array_map(fn (TroopCard $card): string => $card->color->value, $troopCards);
        $strengths = array_map(fn (TroopCard $card): int => $card->strength, $troopCards);
        sort($strengths);

        $sameColor = count(array_unique($colors)) === 1;
        $sameStrength = count(array_unique($strengths)) === 1;
        $consecutiveStrengths = count(array_unique($strengths)) === 3
            && $strengths[1] === $strengths[0] + 1
            && $strengths[2] === $strengths[1] + 1;

        $rank = match (true) {
            $sameColor && $consecutiveStrengths => FormationRank::Wedge,
            $sameStrength => FormationRank::Phalanx,
            $sameColor => FormationRank::BattalionOrder,
            $consecutiveStrengths => FormationRank::SkirmishLine,
            default => FormationRank::Host,
        };

        return new Formation(
            rank: $rank,
            strength: array_sum($strengths),
            cards: array_values($troopCards),
        );
    }
}

<?php

namespace App\Domain\Game\Evaluators;

use App\Domain\Game\Entities\GameState;
use App\Domain\Game\ValueObjects\TroopCard;

final class FlagClaimEvaluator
{
    public function __construct(
        private readonly FormationEvaluator $formationEvaluator = new FormationEvaluator,
    ) {}

    public function canClaim(GameState $state, string $playerId, int $flagIndex): bool
    {
        $flag = $state->flag($flagIndex);

        if ($flag->isClaimed()) {
            return false;
        }

        $claimantCards = $flag->cardsFor($playerId);

        if (count($claimantCards) !== 3) {
            return false;
        }

        $claimantFormation = $this->formationEvaluator->evaluate($claimantCards);
        $opponentId = $state->opponentIdOf($playerId);
        $opponentCards = $flag->cardsFor($opponentId);

        if (count($opponentCards) === 3) {
            $opponentFormation = $this->formationEvaluator->evaluate($opponentCards);
            $comparison = $claimantFormation->compare($opponentFormation);

            if ($comparison > 0) {
                return true;
            }

            if ($comparison < 0) {
                return false;
            }

            return $flag->lastPlayedBy() === $opponentId;
        }

        $remainingCards = $this->remainingUnknownTroops($state);
        $cardsNeeded = 3 - count($opponentCards);

        foreach ($this->combinations($remainingCards, $cardsNeeded) as $possibleCards) {
            $possibleFormation = $this->formationEvaluator->evaluate(array_merge($opponentCards, $possibleCards));

            if ($possibleFormation->compare($claimantFormation) > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, TroopCard>
     */
    private function remainingUnknownTroops(GameState $state): array
    {
        $visibleCardIds = [];

        foreach ($state->flags as $flag) {
            foreach ($flag->cardsByPlayer as $cards) {
                foreach ($cards as $placedCard) {
                    $visibleCardIds[$placedCard->card->id] = true;
                }
            }
        }

        return array_values(array_filter(
            TroopCard::standardDeck(),
            fn (TroopCard $card): bool => ! isset($visibleCardIds[$card->id]),
        ));
    }

    /**
     * @param  array<int, TroopCard>  $cards
     * @return array<int, array<int, TroopCard>>
     */
    private function combinations(array $cards, int $choose): array
    {
        if ($choose === 0) {
            return [[]];
        }

        if ($choose > count($cards)) {
            return [];
        }

        if ($choose === 1) {
            return array_map(fn (TroopCard $card): array => [$card], $cards);
        }

        $combinations = [];

        foreach ($cards as $index => $card) {
            $remainingCards = array_slice($cards, $index + 1);

            foreach ($this->combinations($remainingCards, $choose - 1) as $combination) {
                $combinations[] = array_merge([$card], $combination);
            }
        }

        return $combinations;
    }
}

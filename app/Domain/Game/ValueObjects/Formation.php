<?php

namespace App\Domain\Game\ValueObjects;

use App\Domain\Game\Enums\FormationRank;
use InvalidArgumentException;

final readonly class Formation
{
    /**
     * @param  array<int, TroopCard>  $cards
     */
    public function __construct(
        public FormationRank $rank,
        public int $strength,
        public array $cards,
    ) {
        if (count($this->cards) !== 3) {
            throw new InvalidArgumentException('A base formation must contain exactly three cards.');
        }
    }

    public function compare(self $other): int
    {
        $rankComparison = $this->rank->value <=> $other->rank->value;

        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        return $this->strength <=> $other->strength;
    }
}

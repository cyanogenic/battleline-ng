<?php

namespace App\Domain\Game\ValueObjects;

use InvalidArgumentException;

final readonly class PlacedTroopCard
{
    public function __construct(
        public TroopCard $card,
        public int $playOrder,
    ) {
        if ($this->playOrder < 1) {
            throw new InvalidArgumentException('Play order must be positive.');
        }
    }
}

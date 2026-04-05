<?php

namespace App\Domain\Game\ValueObjects;

use App\Domain\Game\Enums\TroopColor;
use InvalidArgumentException;

final readonly class TroopCard
{
    public function __construct(
        public string $id,
        public TroopColor $color,
        public int $strength,
    ) {
        if ($this->strength < 1 || $this->strength > 10) {
            throw new InvalidArgumentException('Troop strength must be between 1 and 10.');
        }
    }

    public static function create(TroopColor $color, int $strength): self
    {
        return new self(
            id: sprintf('%s-%d', $color->value, $strength),
            color: $color,
            strength: $strength,
        );
    }

    /**
     * @return array<int, self>
     */
    public static function standardDeck(): array
    {
        $deck = [];

        foreach (TroopColor::cases() as $color) {
            for ($strength = 1; $strength <= 10; $strength++) {
                $deck[] = self::create($color, $strength);
            }
        }

        return $deck;
    }
}

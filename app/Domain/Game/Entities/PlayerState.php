<?php

namespace App\Domain\Game\Entities;

use App\Domain\Game\ValueObjects\TroopCard;
use InvalidArgumentException;

final readonly class PlayerState
{
    /**
     * @param  array<int, TroopCard>  $hand
     * @param  array<int, int>  $claimedFlags
     */
    public function __construct(
        public string $playerId,
        public array $hand,
        public array $claimedFlags = [],
    ) {}

    /**
     * @param  array<int, TroopCard>  $hand
     */
    public static function create(string $playerId, array $hand = []): self
    {
        return new self(playerId: $playerId, hand: array_values($hand));
    }

    public function findCard(string $cardId): ?TroopCard
    {
        foreach ($this->hand as $card) {
            if ($card->id === $cardId) {
                return $card;
            }
        }

        return null;
    }

    public function removeCard(string $cardId): self
    {
        $remainingCards = [];
        $removedCard = false;

        foreach ($this->hand as $card) {
            if (! $removedCard && $card->id === $cardId) {
                $removedCard = true;

                continue;
            }

            $remainingCards[] = $card;
        }

        if (! $removedCard) {
            throw new InvalidArgumentException("Card [{$cardId}] was not found in the player's hand.");
        }

        return new self(playerId: $this->playerId, hand: $remainingCards, claimedFlags: $this->claimedFlags);
    }

    public function addCard(TroopCard $card): self
    {
        $hand = $this->hand;
        $hand[] = $card;

        return new self(playerId: $this->playerId, hand: $hand, claimedFlags: $this->claimedFlags);
    }

    public function withClaimedFlag(int $flagIndex): self
    {
        $claimedFlags = $this->claimedFlags;

        if (! in_array($flagIndex, $claimedFlags, true)) {
            $claimedFlags[] = $flagIndex;
            sort($claimedFlags);
        }

        return new self(playerId: $this->playerId, hand: $this->hand, claimedFlags: $claimedFlags);
    }
}

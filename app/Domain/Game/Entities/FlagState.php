<?php

namespace App\Domain\Game\Entities;

use App\Domain\Game\Exceptions\InvalidGameAction;
use App\Domain\Game\ValueObjects\PlacedTroopCard;
use InvalidArgumentException;

final readonly class FlagState
{
    /**
     * @param  array<string, array<int, PlacedTroopCard>>  $cardsByPlayer
     */
    public function __construct(
        public int $index,
        public ?string $claimedBy,
        public array $cardsByPlayer,
    ) {
        if ($this->index < 0 || $this->index > 8) {
            throw new InvalidArgumentException('Battle Line contains exactly nine flags indexed 0 through 8.');
        }
    }

    /**
     * @param  array<int, string>  $playerIds
     */
    public static function create(int $index, array $playerIds): self
    {
        $cardsByPlayer = [];

        foreach ($playerIds as $playerId) {
            $cardsByPlayer[$playerId] = [];
        }

        return new self(index: $index, claimedBy: null, cardsByPlayer: $cardsByPlayer);
    }

    /**
     * @return array<int, PlacedTroopCard>
     */
    public function cardsFor(string $playerId): array
    {
        return $this->cardsByPlayer[$playerId] ?? [];
    }

    public function isClaimed(): bool
    {
        return $this->claimedBy !== null;
    }

    public function withPlacedCard(string $playerId, PlacedTroopCard $placedCard): self
    {
        if ($this->isClaimed()) {
            throw new InvalidGameAction('You cannot play cards to a claimed flag.');
        }

        $cards = $this->cardsByPlayer;
        $cards[$playerId] ??= [];

        if (count($cards[$playerId]) >= 3) {
            throw new InvalidGameAction('A player may only deploy three troop cards to a flag.');
        }

        $cards[$playerId][] = $placedCard;

        return new self(index: $this->index, claimedBy: $this->claimedBy, cardsByPlayer: $cards);
    }

    public function claimFor(string $playerId): self
    {
        if ($this->isClaimed()) {
            throw new InvalidGameAction('This flag has already been claimed.');
        }

        return new self(index: $this->index, claimedBy: $playerId, cardsByPlayer: $this->cardsByPlayer);
    }

    public function lastPlayedBy(): ?string
    {
        $lastPlayedBy = null;
        $lastPlayOrder = 0;

        foreach ($this->cardsByPlayer as $playerId => $cards) {
            foreach ($cards as $card) {
                if ($card->playOrder > $lastPlayOrder) {
                    $lastPlayOrder = $card->playOrder;
                    $lastPlayedBy = $playerId;
                }
            }
        }

        return $lastPlayedBy;
    }
}

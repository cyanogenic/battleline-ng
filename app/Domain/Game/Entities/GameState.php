<?php

namespace App\Domain\Game\Entities;

use App\Domain\Game\Enums\GamePhase;
use App\Domain\Game\ValueObjects\TroopCard;
use InvalidArgumentException;

final readonly class GameState
{
    /**
     * @param  array<int, string>  $playerOrder
     * @param  array<string, PlayerState>  $players
     * @param  array<int, FlagState>  $flags
     * @param  array<int, TroopCard>  $troopDeck
     */
    public function __construct(
        public array $playerOrder,
        public string $activePlayerId,
        public GamePhase $phase,
        public array $players,
        public array $flags,
        public array $troopDeck,
        public int $nextPlayOrder = 1,
        public ?string $winnerId = null,
    ) {
        if (count($this->playerOrder) !== 2) {
            throw new InvalidArgumentException('Battle Line is a two-player game.');
        }

        foreach ($this->playerOrder as $playerId) {
            if (! isset($this->players[$playerId])) {
                throw new InvalidArgumentException("Missing player state for [{$playerId}].");
            }
        }

        if (count($this->flags) !== 9) {
            throw new InvalidArgumentException('Battle Line requires exactly nine flags.');
        }
    }

    /**
     * @param  array<int, TroopCard>  $firstPlayerHand
     * @param  array<int, TroopCard>  $secondPlayerHand
     * @param  array<int, TroopCard>  $troopDeck
     */
    public static function create(
        string $firstPlayerId,
        string $secondPlayerId,
        array $firstPlayerHand,
        array $secondPlayerHand,
        array $troopDeck,
        ?string $startingPlayerId = null,
    ): self {
        $playerOrder = [$firstPlayerId, $secondPlayerId];
        $flags = [];

        for ($flagIndex = 0; $flagIndex < 9; $flagIndex++) {
            $flags[$flagIndex] = FlagState::create($flagIndex, $playerOrder);
        }

        return new self(
            playerOrder: $playerOrder,
            activePlayerId: $startingPlayerId ?? $firstPlayerId,
            phase: GamePhase::PlayingCard,
            players: [
                $firstPlayerId => PlayerState::create($firstPlayerId, $firstPlayerHand),
                $secondPlayerId => PlayerState::create($secondPlayerId, $secondPlayerHand),
            ],
            flags: $flags,
            troopDeck: array_values($troopDeck),
        );
    }

    public function player(string $playerId): PlayerState
    {
        return $this->players[$playerId];
    }

    public function opponentIdOf(string $playerId): string
    {
        foreach ($this->playerOrder as $candidatePlayerId) {
            if ($candidatePlayerId !== $playerId) {
                return $candidatePlayerId;
            }
        }

        throw new InvalidArgumentException("Unknown player [{$playerId}].");
    }

    public function flag(int $flagIndex): FlagState
    {
        return $this->flags[$flagIndex];
    }

    public function withPlayer(PlayerState $playerState): self
    {
        $players = $this->players;
        $players[$playerState->playerId] = $playerState;

        return new self(
            playerOrder: $this->playerOrder,
            activePlayerId: $this->activePlayerId,
            phase: $this->phase,
            players: $players,
            flags: $this->flags,
            troopDeck: $this->troopDeck,
            nextPlayOrder: $this->nextPlayOrder,
            winnerId: $this->winnerId,
        );
    }

    public function withFlag(FlagState $flagState): self
    {
        $flags = $this->flags;
        $flags[$flagState->index] = $flagState;
        ksort($flags);

        return new self(
            playerOrder: $this->playerOrder,
            activePlayerId: $this->activePlayerId,
            phase: $this->phase,
            players: $this->players,
            flags: $flags,
            troopDeck: $this->troopDeck,
            nextPlayOrder: $this->nextPlayOrder,
            winnerId: $this->winnerId,
        );
    }

    public function withPhase(GamePhase $phase): self
    {
        return new self(
            playerOrder: $this->playerOrder,
            activePlayerId: $this->activePlayerId,
            phase: $phase,
            players: $this->players,
            flags: $this->flags,
            troopDeck: $this->troopDeck,
            nextPlayOrder: $this->nextPlayOrder,
            winnerId: $this->winnerId,
        );
    }

    public function withActivePlayer(string $activePlayerId): self
    {
        return new self(
            playerOrder: $this->playerOrder,
            activePlayerId: $activePlayerId,
            phase: $this->phase,
            players: $this->players,
            flags: $this->flags,
            troopDeck: $this->troopDeck,
            nextPlayOrder: $this->nextPlayOrder,
            winnerId: $this->winnerId,
        );
    }

    public function withNextPlayOrder(int $nextPlayOrder): self
    {
        return new self(
            playerOrder: $this->playerOrder,
            activePlayerId: $this->activePlayerId,
            phase: $this->phase,
            players: $this->players,
            flags: $this->flags,
            troopDeck: $this->troopDeck,
            nextPlayOrder: $nextPlayOrder,
            winnerId: $this->winnerId,
        );
    }

    public function withWinner(string $winnerId): self
    {
        return new self(
            playerOrder: $this->playerOrder,
            activePlayerId: $this->activePlayerId,
            phase: GamePhase::GameOver,
            players: $this->players,
            flags: $this->flags,
            troopDeck: $this->troopDeck,
            nextPlayOrder: $this->nextPlayOrder,
            winnerId: $winnerId,
        );
    }

    public function drawTroopCardForActivePlayer(): self
    {
        if ($this->troopDeck === []) {
            return $this;
        }

        $drawnCard = $this->troopDeck[0];
        $remainingDeck = array_values(array_slice($this->troopDeck, 1));
        $activePlayer = $this->player($this->activePlayerId)->addCard($drawnCard);

        return new self(
            playerOrder: $this->playerOrder,
            activePlayerId: $this->activePlayerId,
            phase: $this->phase,
            players: array_replace($this->players, [$activePlayer->playerId => $activePlayer]),
            flags: $this->flags,
            troopDeck: $remainingDeck,
            nextPlayOrder: $this->nextPlayOrder,
            winnerId: $this->winnerId,
        );
    }
}

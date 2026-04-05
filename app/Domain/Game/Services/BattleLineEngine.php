<?php

namespace App\Domain\Game\Services;

use App\Domain\Game\Entities\GameState;
use App\Domain\Game\Enums\GamePhase;
use App\Domain\Game\Evaluators\FlagClaimEvaluator;
use App\Domain\Game\Evaluators\VictoryEvaluator;
use App\Domain\Game\Exceptions\InvalidGameAction;
use App\Domain\Game\ValueObjects\PlacedTroopCard;
use App\Domain\Game\ValueObjects\TroopCard;

final class BattleLineEngine
{
    public function __construct(
        private readonly FlagClaimEvaluator $flagClaimEvaluator = new FlagClaimEvaluator,
        private readonly VictoryEvaluator $victoryEvaluator = new VictoryEvaluator,
    ) {}

    /**
     * @param  array<int, TroopCard>  $firstPlayerHand
     * @param  array<int, TroopCard>  $secondPlayerHand
     * @param  array<int, TroopCard>  $troopDeck
     */
    public function startGame(
        string $firstPlayerId,
        string $secondPlayerId,
        array $firstPlayerHand,
        array $secondPlayerHand,
        array $troopDeck,
        ?string $startingPlayerId = null,
    ): GameState {
        return GameState::create(
            firstPlayerId: $firstPlayerId,
            secondPlayerId: $secondPlayerId,
            firstPlayerHand: $firstPlayerHand,
            secondPlayerHand: $secondPlayerHand,
            troopDeck: $troopDeck,
            startingPlayerId: $startingPlayerId,
        );
    }

    public function playTroopCard(GameState $state, string $playerId, string $cardId, int $flagIndex): GameState
    {
        $this->ensureGameIsActive($state);
        $this->ensureActivePlayer($state, $playerId);

        if ($state->phase !== GamePhase::PlayingCard) {
            throw new InvalidGameAction('A troop card can only be played during the card play phase.');
        }

        $player = $state->player($playerId);
        $card = $player->findCard($cardId);

        if ($card === null) {
            throw new InvalidGameAction("Card [{$cardId}] is not in the player's hand.");
        }

        $flag = $state->flag($flagIndex)->withPlacedCard(
            playerId: $playerId,
            placedCard: new PlacedTroopCard(card: $card, playOrder: $state->nextPlayOrder),
        );

        return $state
            ->withPlayer($player->removeCard($cardId))
            ->withFlag($flag)
            ->withNextPlayOrder($state->nextPlayOrder + 1)
            ->withPhase(GamePhase::ClaimingFlags);
    }

    public function claimFlag(GameState $state, string $playerId, int $flagIndex): GameState
    {
        $this->ensureGameIsActive($state);
        $this->ensureActivePlayer($state, $playerId);

        if ($state->phase !== GamePhase::ClaimingFlags) {
            throw new InvalidGameAction('Flags may only be claimed during the claim phase.');
        }

        if (! $this->flagClaimEvaluator->canClaim($state, $playerId, $flagIndex)) {
            throw new InvalidGameAction("Flag [{$flagIndex}] cannot currently be claimed by [{$playerId}].");
        }

        $updatedPlayer = $state->player($playerId)->withClaimedFlag($flagIndex);
        $updatedState = $state
            ->withPlayer($updatedPlayer)
            ->withFlag($state->flag($flagIndex)->claimFor($playerId));

        $winnerId = $this->victoryEvaluator->winner($updatedState);

        if ($winnerId !== null) {
            return $updatedState->withWinner($winnerId);
        }

        return $updatedState;
    }

    public function passTurn(GameState $state, string $playerId): GameState
    {
        $this->ensureGameIsActive($state);
        $this->ensureActivePlayer($state, $playerId);

        if ($state->phase !== GamePhase::PlayingCard) {
            throw new InvalidGameAction('A player may only pass during the card play phase.');
        }

        if ($this->hasLegalTroopPlay($state, $playerId)) {
            throw new InvalidGameAction('A player may only pass when no troop card can be legally played.');
        }

        return $state->withPhase(GamePhase::ClaimingFlags);
    }

    public function finishTurn(GameState $state, string $playerId): GameState
    {
        $this->ensureGameIsActive($state);
        $this->ensureActivePlayer($state, $playerId);

        if ($state->phase !== GamePhase::ClaimingFlags) {
            throw new InvalidGameAction('A turn may only be finished after the claim phase.');
        }

        $drawnState = $state->drawTroopCardForActivePlayer();

        return $drawnState
            ->withActivePlayer($drawnState->opponentIdOf($playerId))
            ->withPhase(GamePhase::PlayingCard);
    }

    public function canClaimFlag(GameState $state, string $playerId, int $flagIndex): bool
    {
        return $this->flagClaimEvaluator->canClaim($state, $playerId, $flagIndex);
    }

    /**
     * @return array<int, int>
     */
    public function claimableFlagIndexes(GameState $state, string $playerId): array
    {
        $claimableFlagIndexes = [];

        foreach ($state->flags as $flag) {
            if ($this->canClaimFlag($state, $playerId, $flag->index)) {
                $claimableFlagIndexes[] = $flag->index;
            }
        }

        return $claimableFlagIndexes;
    }

    /**
     * @return array<int, int>
     */
    public function playableFlagIndexes(GameState $state, string $playerId): array
    {
        $playableFlagIndexes = [];

        if ($state->player($playerId)->hand === []) {
            return $playableFlagIndexes;
        }

        foreach ($state->flags as $flag) {
            if ($flag->isClaimed()) {
                continue;
            }

            if (count($flag->cardsFor($playerId)) < 3) {
                $playableFlagIndexes[] = $flag->index;
            }
        }

        return $playableFlagIndexes;
    }

    public function canPass(GameState $state, string $playerId): bool
    {
        if ($state->phase !== GamePhase::PlayingCard) {
            return false;
        }

        if ($state->activePlayerId !== $playerId) {
            return false;
        }

        return ! $this->hasLegalTroopPlay($state, $playerId);
    }

    public function canFinishTurn(GameState $state, string $playerId): bool
    {
        return $state->phase === GamePhase::ClaimingFlags
            && $state->activePlayerId === $playerId
            && $state->winnerId === null;
    }

    private function ensureGameIsActive(GameState $state): void
    {
        if ($state->phase === GamePhase::GameOver || $state->winnerId !== null) {
            throw new InvalidGameAction('The game has already ended.');
        }
    }

    private function ensureActivePlayer(GameState $state, string $playerId): void
    {
        if ($state->activePlayerId !== $playerId) {
            throw new InvalidGameAction("It is not [{$playerId}]'s turn.");
        }
    }

    private function hasLegalTroopPlay(GameState $state, string $playerId): bool
    {
        $player = $state->player($playerId);

        if ($player->hand === []) {
            return false;
        }

        foreach ($state->flags as $flag) {
            if ($flag->isClaimed()) {
                continue;
            }

            if (count($flag->cardsFor($playerId)) < 3) {
                return true;
            }
        }

        return false;
    }
}

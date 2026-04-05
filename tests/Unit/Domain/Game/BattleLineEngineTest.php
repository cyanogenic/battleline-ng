<?php

use App\Domain\Game\Entities\GameState;
use App\Domain\Game\Enums\FormationRank;
use App\Domain\Game\Enums\GamePhase;
use App\Domain\Game\Enums\TroopColor;
use App\Domain\Game\Evaluators\FormationEvaluator;
use App\Domain\Game\Exceptions\InvalidGameAction;
use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\ValueObjects\PlacedTroopCard;
use App\Domain\Game\ValueObjects\TroopCard;

test('it ranks formations from strongest to weakest', function () {
    $evaluator = new FormationEvaluator;

    $wedge = $evaluator->evaluate([
        card(TroopColor::Red, 3),
        card(TroopColor::Red, 4),
        card(TroopColor::Red, 5),
    ]);
    $phalanx = $evaluator->evaluate([
        card(TroopColor::Red, 8),
        card(TroopColor::Blue, 8),
        card(TroopColor::Green, 8),
    ]);
    $battalion = $evaluator->evaluate([
        card(TroopColor::Blue, 2),
        card(TroopColor::Blue, 7),
        card(TroopColor::Blue, 4),
    ]);
    $skirmish = $evaluator->evaluate([
        card(TroopColor::Yellow, 4),
        card(TroopColor::Red, 5),
        card(TroopColor::Green, 6),
    ]);
    $host = $evaluator->evaluate([
        card(TroopColor::Yellow, 7),
        card(TroopColor::Blue, 2),
        card(TroopColor::Green, 1),
    ]);

    expect($wedge->rank)->toBe(FormationRank::Wedge)
        ->and($phalanx->rank)->toBe(FormationRank::Phalanx)
        ->and($battalion->rank)->toBe(FormationRank::BattalionOrder)
        ->and($skirmish->rank)->toBe(FormationRank::SkirmishLine)
        ->and($host->rank)->toBe(FormationRank::Host)
        ->and($wedge->compare($phalanx))->toBeGreaterThan(0)
        ->and($phalanx->compare($battalion))->toBeGreaterThan(0)
        ->and($battalion->compare($skirmish))->toBeGreaterThan(0)
        ->and($skirmish->compare($host))->toBeGreaterThan(0);
});

test('it compares equal-ranked formations by their total strength', function () {
    $evaluator = new FormationEvaluator;

    $strongerBattalion = $evaluator->evaluate([
        card(TroopColor::Red, 4),
        card(TroopColor::Red, 6),
        card(TroopColor::Red, 3),
    ]);
    $weakerBattalion = $evaluator->evaluate([
        card(TroopColor::Blue, 7),
        card(TroopColor::Blue, 1),
        card(TroopColor::Blue, 3),
    ]);

    expect($strongerBattalion->compare($weakerBattalion))->toBeGreaterThan(0);
});

test('playing a troop card removes it from hand and moves the turn into claim mode', function () {
    $engine = new BattleLineEngine;
    $state = $engine->startGame(
        firstPlayerId: 'alice',
        secondPlayerId: 'bob',
        firstPlayerHand: [
            card(TroopColor::Red, 1),
            card(TroopColor::Red, 2),
            card(TroopColor::Red, 3),
        ],
        secondPlayerHand: [
            card(TroopColor::Blue, 1),
            card(TroopColor::Blue, 2),
            card(TroopColor::Blue, 3),
        ],
        troopDeck: [
            card(TroopColor::Green, 1),
            card(TroopColor::Green, 2),
        ],
        startingPlayerId: 'alice',
    );

    $updatedState = $engine->playTroopCard($state, 'alice', 'red-2', 0);

    expect($updatedState->phase)->toBe(GamePhase::ClaimingFlags)
        ->and($updatedState->player('alice')->hand)->toHaveCount(2)
        ->and($updatedState->flag(0)->cardsFor('alice'))->toHaveCount(1)
        ->and($updatedState->flag(0)->cardsFor('alice')[0]->card->id)->toBe('red-2');
});

test('a completed stronger formation can claim a flag against a completed weaker formation', function () {
    $engine = new BattleLineEngine;
    $state = stateWithBoard([
        0 => [
            'alice' => [card(TroopColor::Red, 3), card(TroopColor::Red, 4), card(TroopColor::Red, 5)],
            'bob' => [card(TroopColor::Blue, 1), card(TroopColor::Green, 1), card(TroopColor::Yellow, 1)],
        ],
    ]);

    expect($engine->canClaimFlag($state, 'alice', 0))->toBeTrue();
});

test('a player can claim a flag when open information proves the opponent cannot beat it', function () {
    $engine = new BattleLineEngine;
    $state = stateWithBoard([
        0 => [
            'alice' => [card(TroopColor::Red, 8), card(TroopColor::Red, 9), card(TroopColor::Red, 10)],
            'bob' => [card(TroopColor::Blue, 8), card(TroopColor::Green, 8)],
        ],
        1 => [
            'alice' => [card(TroopColor::Blue, 6), card(TroopColor::Blue, 7)],
            'bob' => [card(TroopColor::Purple, 8), card(TroopColor::Yellow, 1)],
        ],
    ]);

    expect($engine->canClaimFlag($state, 'alice', 0))->toBeTrue();
});

test('a tied formation loses the flag if the claimant played the last card', function () {
    $engine = new BattleLineEngine;
    $state = stateFromSequence([
        ['alice', 0, card(TroopColor::Red, 1)],
        ['alice', 0, card(TroopColor::Green, 2)],
        ['bob', 0, card(TroopColor::Blue, 1)],
        ['bob', 0, card(TroopColor::Yellow, 2)],
        ['alice', 0, card(TroopColor::Purple, 3)],
        ['bob', 0, card(TroopColor::Orange, 3)],
    ]);

    expect($engine->canClaimFlag($state, 'bob', 0))->toBeFalse();
});

test('claiming three adjacent flags ends the game immediately', function () {
    $engine = new BattleLineEngine;
    $state = stateWithClaims(['alice' => [0, 1], 'bob' => []]);
    $state = stateWithBoard([
        2 => [
            'alice' => [card(TroopColor::Red, 3), card(TroopColor::Red, 4), card(TroopColor::Red, 5)],
            'bob' => [card(TroopColor::Blue, 1), card(TroopColor::Green, 1), card(TroopColor::Yellow, 1)],
        ],
    ], activePlayerId: 'alice', baseState: $state);

    $state = $engine->claimFlag($state, 'alice', 2);

    expect($state->phase)->toBe(GamePhase::GameOver)
        ->and($state->winnerId)->toBe('alice');
});

test('claiming five flags ends the game immediately', function () {
    $engine = new BattleLineEngine;
    $state = stateWithClaims(['alice' => [0, 2, 4, 6], 'bob' => []]);
    $state = stateWithBoard([
        8 => [
            'alice' => [card(TroopColor::Red, 3), card(TroopColor::Red, 4), card(TroopColor::Red, 5)],
            'bob' => [card(TroopColor::Blue, 1), card(TroopColor::Green, 1), card(TroopColor::Yellow, 1)],
        ],
    ], activePlayerId: 'alice', baseState: $state);

    $state = $engine->claimFlag($state, 'alice', 8);

    expect($state->phase)->toBe(GamePhase::GameOver)
        ->and($state->winnerId)->toBe('alice');
});

test('a player may only pass when no legal troop play remains', function () {
    $engine = new BattleLineEngine;
    $state = $engine->startGame(
        firstPlayerId: 'alice',
        secondPlayerId: 'bob',
        firstPlayerHand: [card(TroopColor::Red, 1)],
        secondPlayerHand: [card(TroopColor::Blue, 1)],
        troopDeck: [],
        startingPlayerId: 'alice',
    );

    expect(fn () => $engine->passTurn($state, 'alice'))
        ->toThrow(InvalidGameAction::class);
});

/**
 * @param  array<int, array<string, array<int, TroopCard>>>  $board
 */
function stateWithBoard(array $board, string $activePlayerId = 'alice', ?GameState $baseState = null): GameState
{
    $state = $baseState ?? stateWithClaims(['alice' => [], 'bob' => []]);
    $order = 1;

    foreach ($board as $flagIndex => $cardsByPlayer) {
        $flag = $state->flag($flagIndex);

        foreach (['alice', 'bob'] as $playerId) {
            $playerCards = $cardsByPlayer[$playerId] ?? [];

            foreach ($playerCards as $card) {
                $flag = $flag->withPlacedCard($playerId, new PlacedTroopCard(
                    card: $card,
                    playOrder: $order,
                ));

                $order++;
            }
        }

        $state = $state->withFlag($flag);
    }

    return $state
        ->withActivePlayer($activePlayerId)
        ->withPhase(GamePhase::ClaimingFlags)
        ->withNextPlayOrder($order);
}

/**
 * @param  array<int, array{0: string, 1: int, 2: TroopCard}>  $plays
 */
function stateFromSequence(array $plays): GameState
{
    $state = stateWithClaims(['alice' => [], 'bob' => []]);
    $order = 1;

    foreach ($plays as [$playerId, $flagIndex, $card]) {
        $state = $state->withFlag(
            $state->flag($flagIndex)->withPlacedCard($playerId, new PlacedTroopCard(
                card: $card,
                playOrder: $order,
            ))
        );
        $order++;
    }

    return $state
        ->withPhase(GamePhase::ClaimingFlags)
        ->withNextPlayOrder($order);
}

/**
 * @param  array<string, array<int, int>>  $claims
 */
function stateWithClaims(array $claims): GameState
{
    $engine = new BattleLineEngine;
    $state = $engine->startGame(
        firstPlayerId: 'alice',
        secondPlayerId: 'bob',
        firstPlayerHand: [],
        secondPlayerHand: [],
        troopDeck: [],
        startingPlayerId: 'alice',
    );

    foreach ($claims as $playerId => $flagIndices) {
        foreach ($flagIndices as $flagIndex) {
            $state = $state
                ->withPlayer($state->player($playerId)->withClaimedFlag($flagIndex))
                ->withFlag($state->flag($flagIndex)->claimFor($playerId));
        }
    }

    return $state->withPhase(GamePhase::ClaimingFlags);
}

function card(TroopColor $color, int $strength): TroopCard
{
    return TroopCard::create($color, $strength);
}

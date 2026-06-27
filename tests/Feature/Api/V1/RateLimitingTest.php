<?php

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('api login is rate limited', function () {
    User::factory()->create([
        'email' => 'alice@example.com',
        'password' => 'password',
    ]);

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'alice@example.com',
            'password' => 'wrong-password',
            'device_name' => 'Alice Phone',
        ])->assertInvalid(['email']);
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'alice@example.com',
        'password' => 'wrong-password',
        'device_name' => 'Alice Phone',
    ])->assertTooManyRequests();
});

test('battle actions are rate limited', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $game = createRateLimitedGame($playerOne, $playerTwo);

    Sanctum::actingAs($playerOne, ['*']);

    foreach (range(1, 60) as $attempt) {
        $this->postJson("/api/v1/games/{$game->id}/actions", [
            'type' => 'pass',
            'state_version' => 1,
        ])->assertConflict();
    }

    $this->postJson("/api/v1/games/{$game->id}/actions", [
        'type' => 'pass',
        'state_version' => 1,
    ])->assertTooManyRequests();
});

function createRateLimitedGame(User $playerOne, User $playerTwo): BattleLineGame
{
    $engine = new BattleLineEngine;
    $serializer = new GameStateSerializer;
    $deck = TroopCard::standardDeck();
    $state = $engine->startGame(
        firstPlayerId: BattleLineGame::PlayerOneSeat,
        secondPlayerId: BattleLineGame::PlayerTwoSeat,
        firstPlayerHand: array_slice($deck, 0, 7),
        secondPlayerHand: array_slice($deck, 7, 7),
        troopDeck: array_slice($deck, 14),
        startingPlayerId: BattleLineGame::PlayerOneSeat,
    );

    return BattleLineGame::query()->create([
        'player_one_user_id' => $playerOne->getKey(),
        'player_two_user_id' => $playerTwo->getKey(),
        'player_one_name' => $playerOne->name,
        'player_two_name' => $playerTwo->name,
        'status' => $state->phase->value,
        'winner_user_id' => null,
        'winner_name' => null,
        'state' => $serializer->serialize($state),
    ]);
}

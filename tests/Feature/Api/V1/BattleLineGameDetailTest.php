<?php

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a participant receives their authenticated player-specific game state from the v1 api', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $game = createStartedApiGame($playerOne, $playerTwo);

    Sanctum::actingAs($playerTwo, ['*']);

    $this->getJson("/api/v1/games/{$game->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.viewer_player_id', BattleLineGame::PlayerTwoSeat)
        ->assertJsonPath('data.player_one_name', 'Alice')
        ->assertJsonPath('data.player_two_name', 'Bob')
        ->assertJsonPath('data.state.viewer.player_id', BattleLineGame::PlayerTwoSeat)
        ->assertJsonPath('data.state.viewer.player_name', 'Bob')
        ->assertJsonPath('data.state.opponent.player_id', BattleLineGame::PlayerOneSeat)
        ->assertJsonPath('data.state.opponent.player_name', 'Alice')
        ->assertJsonPath('data.state.opponent.hand', null)
        ->assertJsonCount(7, 'data.state.viewer.hand');
});

test('a non participant cannot read a battle from the v1 api', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $stranger = User::factory()->create(['name' => 'Eve']);
    $game = createStartedApiGame($playerOne, $playerTwo);

    Sanctum::actingAs($stranger, ['*']);

    $this->getJson("/api/v1/games/{$game->id}")->assertForbidden();
});

function createStartedApiGame(User $playerOne, User $playerTwo): BattleLineGame
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

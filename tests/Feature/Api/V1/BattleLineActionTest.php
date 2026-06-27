<?php

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('a participant can execute a troop action from their authenticated seat via the v1 api', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $game = createStartedActionGame($playerOne, $playerTwo);
    $cardId = $game->state['players'][BattleLineGame::PlayerOneSeat]['hand'][0]['id'];

    Sanctum::actingAs($playerOne, ['*']);

    $response = $this->postJson("/api/v1/games/{$game->id}/actions", [
        'type' => 'play_troop',
        'card_id' => $cardId,
        'flag_index' => 0,
        'state_version' => 0,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.viewer_player_id', BattleLineGame::PlayerOneSeat)
        ->assertJsonPath('data.state_version', 1)
        ->assertJsonPath('data.state.turn.phase', 'claiming_flags')
        ->assertJsonPath('data.state.board.flags.0.viewer_cards.0.card.id', $cardId);
});

test('the v1 api rejects stale battle action versions with a conflict response', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $game = createStartedActionGame($playerOne, $playerTwo);

    Sanctum::actingAs($playerOne, ['*']);

    $this->postJson("/api/v1/games/{$game->id}/actions", [
        'type' => 'pass',
        'state_version' => 1,
    ])->assertConflict()
        ->assertJsonPath('message', 'The battle state is outdated. Refresh and try again.');
});

function createStartedActionGame(User $playerOne, User $playerTwo): BattleLineGame
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

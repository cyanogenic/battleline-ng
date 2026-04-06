<?php

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a participant receives their authenticated player-specific game state', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $game = createStartedGame($playerOne, $playerTwo);

    $response = $this
        ->actingAs($playerTwo)
        ->getJson(route('battle-line-games.show', $game));

    $response->assertSuccessful()
        ->assertJsonPath('data.viewer_player_id', BattleLineGame::PlayerTwoSeat)
        ->assertJsonPath('data.player_one_name', 'Alice')
        ->assertJsonPath('data.player_two_name', 'Bob')
        ->assertJsonPath('data.state.viewer.player_id', BattleLineGame::PlayerTwoSeat)
        ->assertJsonPath('data.state.viewer.player_name', 'Bob')
        ->assertJsonPath('data.state.opponent.player_id', BattleLineGame::PlayerOneSeat)
        ->assertJsonPath('data.state.opponent.player_name', 'Alice')
        ->assertJsonPath('data.state.opponent.hand', null)
        ->assertJsonPath('data.state.turn.active_player_name', 'Alice')
        ->assertJsonCount(7, 'data.state.viewer.hand');
});

test('a participant can execute a troop action from their authenticated seat', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $game = createStartedGame($playerOne, $playerTwo);
    $cardId = $game->state['players'][BattleLineGame::PlayerOneSeat]['hand'][0]['id'];

    $response = $this
        ->actingAs($playerOne)
        ->postJson(route('battle-line-games.actions.store', $game), [
            'type' => 'play_troop',
            'card_id' => $cardId,
            'flag_index' => 0,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.viewer_player_id', BattleLineGame::PlayerOneSeat)
        ->assertJsonPath('data.state.turn.phase', 'claiming_flags')
        ->assertJsonPath('data.state.board.flags.0.viewer_cards.0.card.id', $cardId)
        ->assertJsonCount(6, 'data.state.viewer.hand')
        ->assertJsonPath('data.state.opponent.hand', null)
        ->assertJsonPath('data.state.available_actions.can_finish_turn', true);

    $game->refresh();

    expect($game->status)->toBe('claiming_flags')
        ->and($game->state['players'][BattleLineGame::PlayerOneSeat]['hand'])->toHaveCount(6);
});

test('non participants cannot read or act on a battle state', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $stranger = User::factory()->create(['name' => 'Eve']);
    $game = createStartedGame($playerOne, $playerTwo);

    $this->actingAs($stranger)
        ->getJson(route('battle-line-games.show', $game))
        ->assertForbidden();

    $this->actingAs($stranger)
        ->postJson(route('battle-line-games.actions.store', $game), [
            'type' => 'pass',
        ])
        ->assertForbidden();
});

function createStartedGame(User $playerOne, User $playerTwo): BattleLineGame
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

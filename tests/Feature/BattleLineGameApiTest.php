<?php

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it creates and returns a persisted battle line game', function () {
    $response = $this->postJson('/api/battle-line-games', [
        'player_one_name' => 'alice',
        'player_two_name' => 'bob',
        'viewer_player_id' => 'alice',
        'starting_player_name' => 'bob',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.player_one_name', 'alice')
        ->assertJsonPath('data.player_two_name', 'bob')
        ->assertJsonPath('data.viewer_player_id', 'alice')
        ->assertJsonPath('data.state.turn.active_player_id', 'bob')
        ->assertJsonPath('data.state.turn.phase', 'playing_card')
        ->assertJsonPath('data.state.turn.troop_deck_count', 46)
        ->assertJsonPath('data.state.turn.is_viewer_active', false)
        ->assertJsonCount(7, 'data.state.viewer.hand')
        ->assertJsonPath('data.state.viewer.player_id', 'alice')
        ->assertJsonPath('data.state.viewer.hand_count', 7)
        ->assertJsonPath('data.state.opponent.player_id', 'bob')
        ->assertJsonPath('data.state.opponent.hand', null)
        ->assertJsonPath('data.state.opponent.hand_count', 7)
        ->assertJsonPath('data.state.available_actions.can_play_troop', false)
        ->assertJsonPath('data.state.available_actions.can_finish_turn', false);

    expect(BattleLineGame::query()->count())->toBe(1)
        ->and(BattleLineGame::query()->first()->state['players']['alice']['hand'])->toHaveCount(7)
        ->and(BattleLineGame::query()->first()->state['players']['bob']['hand'])->toHaveCount(7);
});

test('it returns a player-specific game view when fetching a game', function () {
    $game = createPersistedGame();

    $response = $this->getJson("/api/battle-line-games/{$game->id}?viewer_player_id=bob");

    $response->assertSuccessful()
        ->assertJsonPath('data.viewer_player_id', 'bob')
        ->assertJsonPath('data.state.viewer.player_id', 'bob')
        ->assertJsonPath('data.state.opponent.player_id', 'alice')
        ->assertJsonPath('data.state.opponent.hand', null)
        ->assertJsonPath('data.state.opponent.hand_count', 7)
        ->assertJsonCount(7, 'data.state.viewer.hand')
        ->assertJsonPath('data.state.viewer.hand_count', 7);
});

test('it executes a troop play action and persists the updated state', function () {
    $game = createPersistedGame();
    $aliceCardId = $game->state['players']['alice']['hand'][0]['id'];

    $response = $this->postJson("/api/battle-line-games/{$game->id}/actions", [
        'player_id' => 'alice',
        'type' => 'play_troop',
        'card_id' => $aliceCardId,
        'flag_index' => 0,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.state.turn.phase', 'claiming_flags')
        ->assertJsonPath('data.viewer_player_id', 'alice')
        ->assertJsonPath('data.state.board.flags.0.viewer_cards.0.card.id', $aliceCardId)
        ->assertJsonCount(6, 'data.state.viewer.hand')
        ->assertJsonPath('data.state.opponent.hand', null)
        ->assertJsonPath('data.state.opponent.hand_count', 7)
        ->assertJsonPath('data.state.available_actions.can_play_troop', false)
        ->assertJsonPath('data.state.available_actions.can_finish_turn', true);

    expect($response->json('data.state.available_actions.claimable_flag_indexes'))->toBeArray();

    $game->refresh();

    expect($game->status)->toBe('claiming_flags')
        ->and($game->state['players']['alice']['hand'])->toHaveCount(6);
});

test('it returns a validation error when an illegal action is attempted', function () {
    $game = createPersistedGame();

    $response = $this->postJson("/api/battle-line-games/{$game->id}/actions", [
        'player_id' => 'bob',
        'type' => 'play_troop',
        'card_id' => $game->state['players']['bob']['hand'][0]['id'],
        'flag_index' => 0,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['action']);
});

function createPersistedGame(): BattleLineGame
{
    $engine = new BattleLineEngine;
    $serializer = new GameStateSerializer;
    $deck = TroopCard::standardDeck();
    $state = $engine->startGame(
        firstPlayerId: 'alice',
        secondPlayerId: 'bob',
        firstPlayerHand: array_slice($deck, 0, 7),
        secondPlayerHand: array_slice($deck, 7, 7),
        troopDeck: array_slice($deck, 14),
        startingPlayerId: 'alice',
    );

    return BattleLineGame::query()->create([
        'player_one_name' => 'alice',
        'player_two_name' => 'bob',
        'status' => $state->phase->value,
        'winner_name' => null,
        'state' => $serializer->serialize($state),
    ]);
}

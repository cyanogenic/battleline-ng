<?php

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the landing page renders the battle line command interface', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('Command the line. Seize the flags.')
        ->assertSee('Start a New Battle');
});

test('the battle page renders for a selected viewer', function () {
    $game = createPageTestGame();

    $response = $this->get("/battle-line-games/{$game->id}?viewer_player_id=bob");

    $response->assertSuccessful()
        ->assertSee("Battle #{$game->id}")
        ->assertSee('View as bob')
        ->assertSee('Nine contested flags')
        ->assertSee('Field Intel')
        ->assertSee('Selected Card');
});

function createPageTestGame(): BattleLineGame
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

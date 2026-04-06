<?php

use App\Domain\Game\Services\BattleLineEngine;
use App\Domain\Game\Support\GameStateSerializer;
use App\Domain\Game\ValueObjects\TroopCard;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the landing page prompts guests to sign in before playing', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('Command the line. Face a real opponent.')
        ->assertSee('Sign in to start playing')
        ->assertSee('Create Account');
});

test('an authenticated user can create a waiting battle from the lobby', function () {
    $host = User::factory()->create(['name' => 'Alice']);

    $response = $this
        ->actingAs($host)
        ->post(route('battle-line-games.store'));

    $game = BattleLineGame::query()->first();

    $response->assertRedirect(route('battle-line-games.page.show', $game));

    expect($game->player_one_user_id)->toBe($host->getKey())
        ->and($game->player_one_name)->toBe('Alice')
        ->and($game->status)->toBe(BattleLineGame::WaitingForOpponentStatus)
        ->and($game->state)->toBe([]);
});

test('an authenticated user with an open waiting battle cannot create another one', function () {
    $host = User::factory()->create(['name' => 'Alice']);
    createWaitingGame($host);

    $response = $this
        ->from(route('battle-line-games.page.index'))
        ->actingAs($host)
        ->post(route('battle-line-games.store'));

    $response->assertRedirect(route('battle-line-games.page.index'))
        ->assertSessionHasErrors(['game']);

    expect(BattleLineGame::query()->count())->toBe(1);
});

test('an authenticated user with an ongoing battle cannot create another one', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    createStartedPageGame($playerOne, $playerTwo);

    $response = $this
        ->from(route('battle-line-games.page.index'))
        ->actingAs($playerOne)
        ->post(route('battle-line-games.store'));

    $response->assertRedirect(route('battle-line-games.page.index'))
        ->assertSessionHasErrors(['game']);

    expect(BattleLineGame::query()->count())->toBe(1);
});

test('another authenticated user can join a waiting battle and start the match', function () {
    $host = User::factory()->create(['name' => 'Alice']);
    $joiner = User::factory()->create(['name' => 'Bob']);
    $game = createWaitingGame($host);

    $response = $this
        ->actingAs($joiner)
        ->post(route('battle-line-games.join', $game));

    $response->assertRedirect(route('battle-line-games.page.show', $game));

    $game->refresh();

    expect($game->player_two_user_id)->toBe($joiner->getKey())
        ->and($game->player_two_name)->toBe('Bob')
        ->and($game->status)->toBe('playing_card')
        ->and($game->state['players'][BattleLineGame::PlayerOneSeat]['hand'])->toHaveCount(7)
        ->and($game->state['players'][BattleLineGame::PlayerTwoSeat]['hand'])->toHaveCount(7);
});

test('an authenticated user with an open battle cannot join another one', function () {
    $host = User::factory()->create(['name' => 'Alice']);
    $joiner = User::factory()->create(['name' => 'Bob']);
    $otherHost = User::factory()->create(['name' => 'Carol']);
    createWaitingGame($joiner);
    $gameToJoin = createWaitingGame($otherHost);

    $response = $this
        ->from(route('battle-line-games.page.index'))
        ->actingAs($joiner)
        ->post(route('battle-line-games.join', $gameToJoin));

    $response->assertRedirect(route('battle-line-games.page.index'))
        ->assertSessionHasErrors(['game']);

    $gameToJoin->refresh();

    expect($gameToJoin->player_two_user_id)->toBeNull()
        ->and($gameToJoin->status)->toBe(BattleLineGame::WaitingForOpponentStatus);
});

test('a participant can open the live battle page from their own authenticated seat', function () {
    $playerOne = User::factory()->create(['name' => 'Alice']);
    $playerTwo = User::factory()->create(['name' => 'Bob']);
    $game = createStartedPageGame($playerOne, $playerTwo);

    $response = $this
        ->actingAs($playerOne)
        ->get(route('battle-line-games.page.show', $game));

    $response->assertSuccessful()
        ->assertSeeText('Command Hall')
        ->assertSee("Battle #{$game->id}")
        ->assertSeeText('Commander Alice')
        ->assertSeeText('Close')
        ->assertSeeText('Waiting for battle state...')
        ->assertSee('Choose a card, choose a flag, then confirm the deployment.')
        ->assertSee('Nine contested flags')
        ->assertSeeText('Orders')
        ->assertSeeText('Seat Rail')
        ->assertSeeText('Seats')
        ->assertSeeText('Field Intel')
        ->assertSeeText('Live tactical read for all nine lines.')
        ->assertSee('data-account-menu', false)
        ->assertSee('data-open-feedback-modal', false)
        ->assertSee('data-feedback-modal', false)
        ->assertSee('data-player-panel-shell="viewer"', false)
        ->assertSee('data-player-panel-shell="opponent"', false)
        ->assertDontSee('max-w-[1600px]', false)
        ->assertDontSeeText('Back')
        ->assertDontSeeText('Battle Line')
        ->assertDontSee('playing card')
        ->assertSeeInOrder(['Orders', 'Battlefield', 'Seats'])
        ->assertDontSee('Deployment preview')
        ->assertDontSee('View as');
});

function createWaitingGame(User $host): BattleLineGame
{
    return BattleLineGame::query()->create([
        'player_one_user_id' => $host->getKey(),
        'player_one_name' => $host->name,
        'player_two_name' => 'Awaiting challenger',
        'status' => BattleLineGame::WaitingForOpponentStatus,
        'winner_user_id' => null,
        'winner_name' => null,
        'state' => [],
    ]);
}

function createStartedPageGame(User $playerOne, User $playerTwo): BattleLineGame
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

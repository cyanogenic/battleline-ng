<?php

use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an authenticated api user can create a waiting battle', function () {
    $user = User::factory()->create(['name' => 'Commander Alice']);

    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/games');

    $response->assertCreated()
        ->assertJsonPath('data.player_one_name', 'Commander Alice')
        ->assertJsonPath('data.player_two_name', 'Awaiting challenger')
        ->assertJsonPath('data.status', BattleLineGame::WaitingForOpponentStatus);

    expect(BattleLineGame::query()->count())->toBe(1)
        ->and(BattleLineGame::query()->first()?->player_one_user_id)->toBe($user->id);
});

test('an authenticated api user can join a waiting battle', function () {
    $host = User::factory()->create(['name' => 'Commander Host']);
    $joiningUser = User::factory()->create(['name' => 'Commander Joiner']);
    $game = BattleLineGame::query()->create([
        'player_one_user_id' => $host->getKey(),
        'player_two_user_id' => null,
        'player_one_name' => $host->name,
        'player_two_name' => 'Awaiting challenger',
        'status' => BattleLineGame::WaitingForOpponentStatus,
        'winner_user_id' => null,
        'winner_name' => null,
        'state' => [],
    ]);

    Sanctum::actingAs($joiningUser, ['*']);

    $response = $this->postJson("/api/v1/games/{$game->id}/join");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $game->id)
        ->assertJsonPath('data.player_two_name', 'Commander Joiner');

    $game->refresh();

    expect($game->player_two_user_id)->toBe($joiningUser->id)
        ->and($game->status)->not->toBe(BattleLineGame::WaitingForOpponentStatus)
        ->and($game->state)->not->toBe([]);
});

test('an authenticated api user cannot create a second open battle', function () {
    $user = User::factory()->create(['name' => 'Commander Alice']);
    BattleLineGame::query()->create([
        'player_one_user_id' => $user->getKey(),
        'player_two_user_id' => null,
        'player_one_name' => $user->name,
        'player_two_name' => 'Awaiting challenger',
        'status' => BattleLineGame::WaitingForOpponentStatus,
        'winner_user_id' => null,
        'winner_name' => null,
        'state' => [],
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/games')
        ->assertInvalid([
            'game' => 'You already have an open battle. Finish it before creating a new one.',
        ]);
});

test('an authenticated api user with an open battle cannot join another waiting battle', function () {
    $host = User::factory()->create(['name' => 'Commander Host']);
    $joiningUser = User::factory()->create(['name' => 'Commander Joiner']);

    BattleLineGame::query()->create([
        'player_one_user_id' => $joiningUser->getKey(),
        'player_two_user_id' => null,
        'player_one_name' => $joiningUser->name,
        'player_two_name' => 'Awaiting challenger',
        'status' => BattleLineGame::WaitingForOpponentStatus,
        'winner_user_id' => null,
        'winner_name' => null,
        'state' => [],
    ]);

    $joinableGame = BattleLineGame::query()->create([
        'player_one_user_id' => $host->getKey(),
        'player_two_user_id' => null,
        'player_one_name' => $host->name,
        'player_two_name' => 'Awaiting challenger',
        'status' => BattleLineGame::WaitingForOpponentStatus,
        'winner_user_id' => null,
        'winner_name' => null,
        'state' => [],
    ]);

    Sanctum::actingAs($joiningUser, ['*']);

    $this->postJson("/api/v1/games/{$joinableGame->id}/join")
        ->assertInvalid([
            'game' => 'You already have an open battle. Finish it before joining another one.',
        ]);
});

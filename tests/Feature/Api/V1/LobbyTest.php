<?php

use App\Domain\Game\Enums\GamePhase;
use App\Models\BattleLineGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an authenticated user can fetch their lobby snapshot', function () {
    $user = User::factory()->create(['name' => 'Commander Alice']);
    $opponent = User::factory()->create(['name' => 'Commander Bob']);
    $challenger = User::factory()->create(['name' => 'Commander Carol']);
    $outsider = User::factory()->create(['name' => 'Commander Eve']);

    $finishedGame = createLobbyGame(
        playerOne: $user,
        playerTwo: $opponent,
        status: GamePhase::GameOver->value,
    );
    $openGame = createLobbyGame(
        playerOne: $user,
        playerTwo: $opponent,
        status: 'claiming_flags',
    );
    $joinableGame = createLobbyGame(
        playerOne: $challenger,
        playerTwo: null,
        status: BattleLineGame::WaitingForOpponentStatus,
    );
    createLobbyGame(
        playerOne: $outsider,
        playerTwo: $challenger,
        status: 'claiming_flags',
    );

    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/lobby');

    $response->assertSuccessful()
        ->assertJsonPath('data.open_game.id', $openGame->id)
        ->assertJsonCount(2, 'data.my_games')
        ->assertJsonCount(1, 'data.joinable_games')
        ->assertJsonPath('data.joinable_games.0.id', $joinableGame->id);

    expect(collect($response->json('data.my_games'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$openGame->id, $finishedGame->id])->sort()->values()->all());
});

function createLobbyGame(User $playerOne, ?User $playerTwo, string $status): BattleLineGame
{
    return BattleLineGame::query()->create([
        'player_one_user_id' => $playerOne->getKey(),
        'player_two_user_id' => $playerTwo?->getKey(),
        'player_one_name' => $playerOne->name,
        'player_two_name' => $playerTwo?->name ?? 'Awaiting challenger',
        'status' => $status,
        'winner_user_id' => null,
        'winner_name' => null,
        'state' => [],
    ]);
}

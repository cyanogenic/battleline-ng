<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest can register for api access and receive a bearer token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Commander Alice',
        'email' => 'alice@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'device_name' => 'Alice iPhone',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.user.name', 'Commander Alice')
        ->assertJsonPath('data.user.email', 'alice@example.com')
        ->assertJsonPath('data.token_type', 'Bearer');

    $user = User::query()->where('email', 'alice@example.com')->firstOrFail();

    expect($response->json('data.token'))->toBeString()->not->toBe('')
        ->and($user->tokens()->where('name', 'Alice iPhone')->exists())->toBeTrue();
});

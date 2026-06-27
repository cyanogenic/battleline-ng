<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an existing user can log in for api access and receive a bearer token', function () {
    User::factory()->create([
        'name' => 'Commander Bob',
        'email' => 'bob@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'bob@example.com',
        'password' => 'password',
        'device_name' => 'Bob Android',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.user.name', 'Commander Bob')
        ->assertJsonPath('data.user.email', 'bob@example.com')
        ->assertJsonPath('data.token_type', 'Bearer');

    $user = User::query()->where('email', 'bob@example.com')->firstOrFail();

    expect($response->json('data.token'))->toBeString()->not->toBe('')
        ->and($user->tokens()->where('name', 'Bob Android')->exists())->toBeTrue();
});

test('api login rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'eve@example.com',
        'password' => 'password',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'eve@example.com',
        'password' => 'wrong-password',
        'device_name' => 'Eve Laptop',
    ])->assertInvalid([
        'email' => 'These credentials do not match our records.',
    ]);
});

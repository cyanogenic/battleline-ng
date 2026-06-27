<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('logging out revokes only the current api token', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('Current Device')->plainTextToken;
    $otherToken = $user->createToken('Other Device');

    $this->withHeader('Authorization', 'Bearer '.$currentToken)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($user->fresh()->tokens()->count())->toBe(1)
        ->and($user->tokens()->where('id', $otherToken->accessToken->id)->exists())->toBeTrue();
});

test('logging out all devices revokes every api token', function () {
    $user = User::factory()->create();
    $currentToken = $user->createToken('Current Device')->plainTextToken;
    $user->createToken('Tablet');

    $this->withHeader('Authorization', 'Bearer '.$currentToken)
        ->postJson('/api/v1/auth/logout-all')
        ->assertNoContent();

    expect($user->fresh()->tokens()->count())->toBe(0);
});

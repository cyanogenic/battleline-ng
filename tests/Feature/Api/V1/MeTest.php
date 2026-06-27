<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('an authenticated api user can fetch their profile', function () {
    $user = User::factory()->create([
        'name' => 'Commander Carol',
        'email' => 'carol@example.com',
    ]);

    Sanctum::actingAs($user, ['*']);

    $this->getJson('/api/v1/me')
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Commander Carol')
        ->assertJsonPath('data.email', 'carol@example.com');
});

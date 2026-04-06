<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a guest can register and enter the command hall', function () {
    $response = $this->post('/register', [
        'name' => 'Commander Alice',
        'email' => 'alice@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('battle-line-games.page.index'));

    $this->assertAuthenticated();

    expect(User::query()->where('email', 'alice@example.com')->exists())->toBeTrue();
});

test('an existing user can sign in from the login page', function () {
    $user = User::factory()->create([
        'name' => 'Commander Bob',
        'email' => 'bob@example.com',
    ]);

    $response = $this->post('/login', [
        'email' => 'bob@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect(route('battle-line-games.page.index'));
    $this->assertAuthenticatedAs($user);
});

<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('auth:sanctum')->get('/api/v1/test-authenticated', fn () => response()->json([
        'ok' => true,
    ]));
});

test('api routes always render json exceptions', function () {
    $response = $this->get('/api/v1/missing-route');

    $response->assertNotFound();

    expect($response->headers->get('content-type'))
        ->toContain('application/json')
        ->and($response->json('message'))
        ->not->toBeNull();
});

test('sanctum protected api routes reject unauthenticated requests', function () {
    $this->getJson('/api/v1/test-authenticated')->assertUnauthorized();
});

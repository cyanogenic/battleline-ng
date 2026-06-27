# Battleline API

## Overview

- Base path: `/api/v1`
- Response format: JSON
- Authenticated endpoints use Sanctum bearer tokens
- Successful responses use a top-level `data` field unless the endpoint returns `204 No Content`

## Authentication

Clients should send these headers on every API request:

```http
Accept: application/json
Authorization: Bearer {token}
```

Register and login both return a Sanctum personal access token. The client should store that token securely and send it as a bearer token on subsequent requests.

## Response Conventions

### Success envelope

Most successful responses use this shape:

```json
{
  "data": {}
}
```

### Validation errors

Laravel validation failures return `422 Unprocessable Entity`:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field": [
      "The field field is required."
    ]
  }
}
```

### Common status codes

- `200 OK`: read or mutate successfully
- `201 Created`: resource created
- `204 No Content`: logout succeeded
- `401 Unauthorized`: missing or invalid token
- `403 Forbidden`: authenticated but not allowed to access the game
- `409 Conflict`: stale `state_version` when submitting a battle action
- `422 Unprocessable Entity`: validation or business-rule failure
- `429 Too Many Requests`: rate limit exceeded

## Resources

### User

```json
{
  "id": 1,
  "name": "Commander Alice",
  "email": "alice@example.com"
}
```

### BattleLineGameSummary

```json
{
  "id": 12,
  "player_one_name": "Commander Alice",
  "player_two_name": "Commander Bob",
  "status": "claiming_flags",
  "state_version": 3,
  "winner_name": null,
  "updated_at": "2026-06-27T15:30:00.000000Z"
}
```

### BattleLineGameDetail

```json
{
  "id": 12,
  "player_one_name": "Commander Alice",
  "player_two_name": "Commander Bob",
  "status": "claiming_flags",
  "state_version": 3,
  "winner_name": null,
  "viewer_player_id": "player_one",
  "state": {}
}
```

Notes:

- `state` is a viewer-specific projection of the game state.
- The authenticated player's hand is included in `state.viewer.hand`.
- The opponent hand is intentionally hidden and returned as `null`.
- `viewer_player_id` identifies the authenticated player seat for this game.

## Endpoints

### `POST /api/v1/auth/register`

Create a user account and immediately issue a bearer token.

Request body:

```json
{
  "name": "Commander Alice",
  "email": "alice@example.com",
  "password": "password",
  "password_confirmation": "password",
  "device_name": "Alice iPhone"
}
```

Response:

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Commander Alice",
      "email": "alice@example.com"
    },
    "token": "plain-text-token",
    "token_type": "Bearer"
  }
}
```

### `POST /api/v1/auth/login`

Issue a new bearer token for an existing user.

Request body:

```json
{
  "email": "alice@example.com",
  "password": "password",
  "device_name": "Alice iPhone"
}
```

Success response:

```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Commander Alice",
      "email": "alice@example.com"
    },
    "token": "plain-text-token",
    "token_type": "Bearer"
  }
}
```

Invalid credentials return `422` with an `email` validation error.

### `POST /api/v1/auth/logout`

Revoke the current access token.

Request body: none

Response: `204 No Content`

### `POST /api/v1/auth/logout-all`

Revoke all access tokens for the authenticated user.

Request body: none

Response: `204 No Content`

### `GET /api/v1/me`

Return the authenticated user profile.

Response:

```json
{
  "data": {
    "id": 1,
    "name": "Commander Alice",
    "email": "alice@example.com"
  }
}
```

### `GET /api/v1/lobby`

Return the lobby snapshot for the authenticated user.

Response:

```json
{
  "data": {
    "open_game": null,
    "my_games": [],
    "joinable_games": []
  }
}
```

Field notes:

- `open_game`: the user's latest unfinished game, or `null`
- `my_games`: up to 6 recent games involving the user
- `joinable_games`: up to 6 open games the user may join
- `open_game`, `my_games[*]`, and `joinable_games[*]` use the `BattleLineGameSummary` shape

### `POST /api/v1/games`

Create a new open battle for the authenticated user.

Request body: none

Success response: `201 Created`

```json
{
  "data": {
    "id": 12,
    "player_one_name": "Commander Alice",
    "player_two_name": "Awaiting challenger",
    "status": "waiting_for_opponent",
    "state_version": 0,
    "winner_name": null,
    "updated_at": "2026-06-27T15:30:00.000000Z"
  }
}
```

If the user already has an open battle, the API returns `422` with a `game` error.

### `POST /api/v1/games/{battleLineGame}/join`

Join an open battle.

Request body: none

Success response:

```json
{
  "data": {
    "id": 12,
    "player_one_name": "Commander Host",
    "player_two_name": "Commander Joiner",
    "status": "playing_card",
    "state_version": 1,
    "winner_name": null,
    "updated_at": "2026-06-27T15:30:00.000000Z"
  }
}
```

If the user already has an open battle, the API returns `422` with a `game` error.

### `GET /api/v1/games/{battleLineGame}`

Return the authenticated participant's game detail view.

Success response:

```json
{
  "data": {
    "id": 12,
    "player_one_name": "Commander Alice",
    "player_two_name": "Commander Bob",
    "status": "claiming_flags",
    "state_version": 3,
    "winner_name": null,
    "viewer_player_id": "player_two",
    "state": {
      "viewer": {
        "player_id": "player_two",
        "player_name": "Commander Bob",
        "hand": []
      },
      "opponent": {
        "player_id": "player_one",
        "player_name": "Commander Alice",
        "hand": null
      }
    }
  }
}
```

Only battle participants may read a game. Non-participants receive `403 Forbidden`.

### `POST /api/v1/games/{battleLineGame}/actions`

Submit an in-game action for the authenticated participant.

Request body:

```json
{
  "type": "play_troop",
  "card_id": "card-id-from-current-hand",
  "flag_index": 0,
  "state_version": 3
}
```

Supported action types:

- `play_troop`: requires `card_id` and `flag_index`
- `claim_flag`: requires `flag_index`
- `pass`: requires only `state_version`
- `finish_turn`: requires only `state_version`

Field rules:

- `state_version` is always required
- `flag_index` must be between `0` and `8`
- Clients should read the latest game detail before sending a mutation

Success response:

```json
{
  "data": {
    "id": 12,
    "state_version": 4,
    "viewer_player_id": "player_one",
    "state": {}
  }
}
```

Concurrency rule:

- If `state_version` does not match the latest persisted version, the API returns `409 Conflict`
- Conflict message: `The battle state is outdated. Refresh and try again.`

## Rate Limits

- `POST /api/v1/auth/register`: 5 requests per minute per IP
- `POST /api/v1/auth/login`: 5 requests per minute per email + IP
- `GET /api/v1/lobby`: 120 requests per minute per authenticated user or guest key
- `GET /api/v1/games/{battleLineGame}`: 120 requests per minute per authenticated user or guest key
- `POST /api/v1/games/{battleLineGame}/actions`: 60 requests per minute per authenticated user or guest key

## Maintenance Rule

Whenever any API route, authentication flow, request field, response payload, status code, error format, or rate limit changes, update this file in the same change.

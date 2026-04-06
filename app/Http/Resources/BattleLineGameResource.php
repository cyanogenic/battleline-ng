<?php

namespace App\Http\Resources;

use App\Domain\Game\Support\GameStateViewProjector;
use App\Models\BattleLineGame;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BattleLineGameResource extends JsonResource
{
    public function __construct(
        mixed $resource,
        private readonly ?string $viewerPlayerId = null,
        private readonly GameStateViewProjector $projector = new GameStateViewProjector,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        /** @var BattleLineGame $game */
        $game = $this->resource;

        return [
            'id' => $game->id,
            'player_one_name' => $game->player_one_name,
            'player_two_name' => $game->player_two_name,
            'status' => $game->status,
            'winner_name' => $game->winner_name,
            'viewer_player_id' => $this->viewerPlayerId,
            'state' => $game->hasStarted()
                ? $this->projector->project($game->state, $this->viewerPlayerId, $game->seatNameMap())
                : null,
        ];
    }
}

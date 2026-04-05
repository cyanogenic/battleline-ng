<?php

namespace App\Http\Resources;

use App\Domain\Game\Support\GameStateViewProjector;
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
        return [
            'id' => $this->resource->id,
            'player_one_name' => $this->resource->player_one_name,
            'player_two_name' => $this->resource->player_two_name,
            'status' => $this->resource->status,
            'winner_name' => $this->resource->winner_name,
            'viewer_player_id' => $this->viewerPlayerId,
            'state' => $this->projector->project($this->resource->state, $this->viewerPlayerId),
        ];
    }
}

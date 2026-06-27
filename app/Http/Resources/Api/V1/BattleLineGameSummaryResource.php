<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BattleLineGame;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BattleLineGameSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BattleLineGame $game */
        $game = $this->resource;

        return [
            'id' => $game->id,
            'player_one_name' => $game->player_one_name,
            'player_two_name' => $game->player_two_name,
            'status' => $game->status,
            'state_version' => $game->state_version,
            'winner_name' => $game->winner_name,
            'updated_at' => $game->updated_at?->toISOString(),
        ];
    }
}

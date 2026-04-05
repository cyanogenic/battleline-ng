<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'player_one_name',
    'player_two_name',
    'status',
    'winner_name',
    'state',
])]
class BattleLineGame extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => 'array',
        ];
    }
}

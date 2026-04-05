<?php

namespace App\Http\Requests;

use App\Models\BattleLineGame;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowBattleLineGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var BattleLineGame $game */
        $game = $this->route('battleLineGame');

        return [
            'viewer_player_id' => [
                'required',
                'string',
                Rule::in([$game->player_one_name, $game->player_two_name]),
            ],
        ];
    }
}

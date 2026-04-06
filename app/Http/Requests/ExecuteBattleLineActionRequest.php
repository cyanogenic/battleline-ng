<?php

namespace App\Http\Requests;

use App\Models\BattleLineGame;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteBattleLineActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BattleLineGame $game */
        $game = $this->route('battleLineGame');

        return $this->user()?->can('act', $game) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['play_troop', 'claim_flag', 'pass', 'finish_turn'])],
            'card_id' => [
                Rule::requiredIf(fn (): bool => $this->input('type') === 'play_troop'),
                'string',
            ],
            'flag_index' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('type'), ['play_troop', 'claim_flag'], true)),
                'integer',
                'between:0,8',
            ],
        ];
    }
}

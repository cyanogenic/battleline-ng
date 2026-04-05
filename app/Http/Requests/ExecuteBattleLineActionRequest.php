<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteBattleLineActionRequest extends FormRequest
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
        return [
            'player_id' => ['required', 'string', 'max:255'],
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

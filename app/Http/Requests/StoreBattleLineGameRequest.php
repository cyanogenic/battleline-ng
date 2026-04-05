<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBattleLineGameRequest extends FormRequest
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
            'player_one_name' => ['required', 'string', 'max:255'],
            'player_two_name' => ['required', 'string', 'max:255', 'different:player_one_name'],
            'viewer_player_id' => [
                'required',
                'string',
                Rule::in([$this->input('player_one_name'), $this->input('player_two_name')]),
            ],
            'starting_player_name' => [
                'nullable',
                'string',
                Rule::in([$this->input('player_one_name'), $this->input('player_two_name')]),
            ],
        ];
    }
}

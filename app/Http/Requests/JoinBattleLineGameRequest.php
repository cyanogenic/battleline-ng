<?php

namespace App\Http\Requests;

use App\Models\BattleLineGame;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class JoinBattleLineGameRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var BattleLineGame $game */
        $game = $this->route('battleLineGame');

        return $this->user()?->can('join', $game) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                if ($user === null) {
                    return;
                }

                if (BattleLineGame::query()->openForUser($user)->exists()) {
                    $validator->errors()->add(
                        'game',
                        'You already have an open battle. Finish it before joining another one.'
                    );
                }
            },
        ];
    }
}

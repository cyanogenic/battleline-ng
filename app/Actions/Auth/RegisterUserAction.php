<?php

namespace App\Actions\Auth;

use App\Models\User;

class RegisterUserAction
{
    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function execute(array $attributes): User
    {
        return User::query()->create($attributes);
    }
}

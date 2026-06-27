<?php

namespace App\Actions\Auth;

use App\Models\User;

class IssueApiTokenAction
{
    public function execute(User $user, string $deviceName): string
    {
        return $user->createToken($deviceName, ['*'])->plainTextToken;
    }
}

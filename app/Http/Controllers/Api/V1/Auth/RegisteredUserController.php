<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\IssueApiTokenAction;
use App\Actions\Auth\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

class RegisteredUserController extends Controller
{
    public function store(
        RegisterRequest $request,
        RegisterUserAction $registerUser,
        IssueApiTokenAction $issueApiToken,
    ): JsonResponse {
        $validated = $request->validated();
        $user = $registerUser->execute([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);
        $token = $issueApiToken->execute($user, $validated['device_name']);

        return response()->json([
            'data' => [
                'user' => UserResource::make($user)->resolve($request),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }
}

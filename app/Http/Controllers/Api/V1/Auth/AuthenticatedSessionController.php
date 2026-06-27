<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\IssueApiTokenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * @throws ValidationException
     */
    public function store(LoginRequest $request, IssueApiTokenAction $issueApiToken): JsonResponse
    {
        $validated = $request->validated();
        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user instanceof User || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $issueApiToken->execute($user, $validated['device_name']);

        return response()->json([
            'data' => [
                'user' => UserResource::make($user)->resolve($request),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function destroy(Request $request): Response
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function destroyAll(Request $request): Response
    {
        $request->user()?->tokens()->delete();

        return response()->noContent();
    }
}

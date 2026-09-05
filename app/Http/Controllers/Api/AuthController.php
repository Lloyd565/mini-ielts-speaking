<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and return an API token.
     */
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $user->createToken('api')->plainTextToken,
            ],
            'message' => 'Registered successfully.',
        ], 201);
    }

    /**
     * Authenticate a user and return an API token.
     */
    public function login(LoginUserRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        $passwordMatches = Hash::check(
            $request->validated('password'),
            $user?->password ?? Hash::make('timing-equaliser'),
        );

        if (! $user || ! $passwordMatches) {
            return response()->json([
                'success' => false,
                'message' => 'The given credentials are incorrect.',
                'errors' => ['email' => ['The given credentials are incorrect.']],
            ], 422);
        }

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $user->createToken('api')->plainTextToken,
            ],
            'message' => 'Logged in successfully.',
        ]);
    }

    /**
     * Revoke the token used to authenticate the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'data' => [],
            'message' => 'Logged out successfully.',
        ]);
    }
}

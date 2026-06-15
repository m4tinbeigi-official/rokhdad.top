<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_e164' => ['nullable', 'string', 'max:20', 'regex:/^\+[1-9]\d{7,14}$/', 'unique:users,phone_e164'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_e164' => $data['phone_e164'] ?? null,
            'password' => $data['password'],
            'status' => 'active',
            'locale' => 'fa',
            'timezone' => 'Asia/Tehran',
            'last_login_at' => now(),
        ]);

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('api')->plainTextToken,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password) || $user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('api')->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'phone_e164' => $user->phone_e164,
            'phone_verified_at' => $user->phone_verified_at,
            'status' => $user->status,
            'locale' => $user->locale,
            'timezone' => $user->timezone,
            'last_login_at' => $user->last_login_at,
        ];
    }
}

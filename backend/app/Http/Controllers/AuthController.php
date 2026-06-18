<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function requestOtp(Request $request, NotificationService $notifications): JsonResponse
    {
        $data = $request->validate([
            'phone_e164' => ['required', 'string', 'max:20', 'regex:/^\+[1-9]\d{7,14}$/'],
            'purpose' => ['nullable', 'string', 'in:login,verify'],
        ]);

        $purpose = $data['purpose'] ?? 'verify';
        $phone = $data['phone_e164'];

        OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('used', false)
            ->update(['used' => true]);

        $code = (string) random_int(100000, 999999);
        $otp = OtpCode::query()->create([
            'phone' => $phone,
            'code' => substr($code, 0, 2).'****',
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'used' => false,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);

        $templateId = (int) config('services.smsir.otp_template_id', 0);
        $userId = User::query()->where('phone_e164', $phone)->value('id');

        $notifications->sendOtp($phone, $templateId, [
            ['name' => 'CODE', 'value' => $code],
        ], $userId);

        return response()->json([
            'message' => 'OTP sent.',
            'expires_at' => $otp->expires_at?->toJSON(),
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone_e164' => ['required', 'string', 'max:20', 'regex:/^\+[1-9]\d{7,14}$/'],
            'purpose' => ['nullable', 'string', 'in:login,verify'],
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $purpose = $data['purpose'] ?? 'verify';

        /** @var OtpCode|null $otp */
        $otp = OtpCode::query()
            ->where('phone', $data['phone_e164'])
            ->where('purpose', $purpose)
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (! $otp || $otp->attempts >= 5 || ! Hash::check($data['code'], (string) $otp->code_hash)) {
            $otp?->incrementAttempts();

            throw ValidationException::withMessages([
                'code' => ['The provided OTP code is invalid or expired.'],
            ]);
        }

        $otp->markUsed();

        /** @var User|null $user */
        $user = User::query()->where('phone_e164', $data['phone_e164'])->first();
        if ($user && ! $user->phone_verified_at) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return response()->json([
            'message' => 'OTP verified.',
            'user' => $user ? $this->userPayload($user->fresh()) : null,
            'token' => $user ? $user->createToken('api')->plainTextToken : null,
            'token_type' => $user ? 'Bearer' : null,
        ]);
    }

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

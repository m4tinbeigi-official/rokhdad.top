<?php

namespace Tests\Feature;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneOtpApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_phone_otp(): void
    {
        Http::fake([
            'api.sms.ir/v1/send/verify' => Http::response(['status' => 1]),
        ]);

        User::factory()->create(['phone_e164' => '+989121234567']);

        $this->postJson('/api/v1/auth/otp/request', [
            'phone_e164' => '+989121234567',
            'purpose' => 'verify',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'OTP sent.')
            ->assertJsonStructure(['expires_at']);

        $otp = OtpCode::query()->where('phone', '+989121234567')->firstOrFail();

        $this->assertSame('verify', $otp->purpose);
        $this->assertSame(6, strlen((string) $otp->code));
        $this->assertStringContainsString('*', (string) $otp->code);
        $this->assertNotNull($otp->code_hash);
        $this->assertDatabaseHas('notification_logs', [
            'recipient' => '+989121234567',
            'type' => 'otp',
            'status' => 'sent',
        ]);
    }

    public function test_user_can_verify_phone_otp_and_receive_token(): void
    {
        $user = User::factory()->create([
            'phone_e164' => '+989121234567',
            'phone_verified_at' => null,
        ]);

        OtpCode::query()->create([
            'phone' => '+989121234567',
            'code' => '12****',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'verify',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone_e164' => '+989121234567',
            'purpose' => 'verify',
            'code' => '123456',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'OTP verified.')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token']);

        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->assertTrue(OtpCode::query()->firstOrFail()->used);
    }

    public function test_invalid_phone_otp_is_rejected_and_attempt_is_counted(): void
    {
        OtpCode::query()->create([
            'phone' => '+989121234567',
            'code' => '12****',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'verify',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone_e164' => '+989121234567',
            'purpose' => 'verify',
            'code' => '000000',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');

        $this->assertSame(1, OtpCode::query()->firstOrFail()->attempts);
    }

    public function test_expired_phone_otp_is_rejected(): void
    {
        OtpCode::query()->create([
            'phone' => '+989121234567',
            'code' => '12****',
            'code_hash' => Hash::make('123456'),
            'purpose' => 'verify',
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/v1/auth/otp/verify', [
            'phone_e164' => '+989121234567',
            'purpose' => 'verify',
            'code' => '123456',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }
}

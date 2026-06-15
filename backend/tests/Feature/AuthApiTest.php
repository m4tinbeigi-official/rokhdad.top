<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Rokhdad User',
            'email' => 'user@example.com',
            'phone_e164' => '+989121234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', 'user@example.com')
            ->assertJsonPath('user.phone_e164', '+989121234567')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'phone_e164' => '+989121234567',
            'status' => 'active',
        ]);
    }

    public function test_user_can_login_view_profile_and_logout(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $token = $login->assertOk()->json('token');
        $this->assertIsString($token);

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'user@example.com');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }
}

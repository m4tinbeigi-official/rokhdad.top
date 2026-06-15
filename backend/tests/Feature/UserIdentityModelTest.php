<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserIdentityModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_contains_identity_columns(): void
    {
        foreach ([
            'phone_e164',
            'phone_verified_at',
            'status',
            'locale',
            'timezone',
            'last_login_at',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('users', $column), "Missing users.$column column");
        }
    }

    public function test_user_identity_fields_are_fillable_and_cast(): void
    {
        $user = User::factory()->phoneVerified()->create([
            'phone_e164' => '+989121234567',
            'status' => 'active',
            'locale' => 'fa',
            'timezone' => 'Asia/Tehran',
            'last_login_at' => now(),
        ]);

        $this->assertSame('+989121234567', $user->phone_e164);
        $this->assertSame('active', $user->status);
        $this->assertSame('fa', $user->locale);
        $this->assertSame('Asia/Tehran', $user->timezone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNotNull($user->last_login_at);
    }
}

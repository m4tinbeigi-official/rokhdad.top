<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_initiate_zarinpal_payment(): void
    {
        config(['services.zarinpal.merchant_id' => 'test-merchant']);
        Http::fake([
            'sandbox.zarinpal.com/pg/v4/payment/request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'AUTH123'],
                'errors' => [],
            ]),
        ]);

        $user = User::factory()->create();
        $registration = $this->registration($user, 250_000);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/registrations/{$registration->id}/pay", [
                'gateway' => 'zarinpal',
            ])
            ->assertOk()
            ->assertJsonPath('data.authority', 'AUTH123');

        $this->assertDatabaseHas('payments', [
            'registration_id' => $registration->id,
            'gateway' => 'zarinpal',
            'gateway_authority' => 'AUTH123',
            'status' => 'pending',
        ]);
    }

    public function test_zarinpal_callback_marks_payment_and_registration_paid(): void
    {
        config(['services.zarinpal.merchant_id' => 'test-merchant']);
        Http::fake([
            'sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => ['code' => 100, 'ref_id' => 'REF123'],
                'errors' => [],
            ]),
        ]);

        $user = User::factory()->create();
        $registration = $this->registration($user, 250_000);
        Payment::query()->create([
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'gateway' => 'zarinpal',
            'gateway_authority' => 'AUTH123',
            'status' => 'pending',
            'amount' => 250_000,
            'currency' => 'IRR',
            'callback_url' => 'https://rokhdad.test/api/v1/payments/callback/zarinpal',
        ]);

        $this->get('/api/v1/payments/callback/zarinpal?Authority=AUTH123&Status=OK')
            ->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'gateway_authority' => 'AUTH123',
            'gateway_ref_id' => 'REF123',
            'status' => 'paid',
        ]);
        $this->assertSame('paid', $registration->fresh()->payment_status);
        $this->assertSame('confirmed', $registration->fresh()->status);
    }

    public function test_user_can_initiate_zibal_payment(): void
    {
        config(['services.zibal.merchant' => 'zibal']);
        Http::fake([
            'gateway.zibal.ir/v1/request' => Http::response([
                'result' => 100,
                'trackId' => 987654,
            ]),
        ]);

        $user = User::factory()->create();
        $registration = $this->registration($user, 320_000);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/registrations/{$registration->id}/pay", [
                'gateway' => 'zibal',
            ])
            ->assertOk()
            ->assertJsonPath('data.authority', '987654');
    }

    private function registration(User $user, int $amount): Registration
    {
        return Registration::query()->create([
            'event_id' => Event::factory()->create(['is_internal' => true])->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'quantity' => 1,
            'total_amount' => $amount,
            'currency' => 'IRR',
        ]);
    }
}

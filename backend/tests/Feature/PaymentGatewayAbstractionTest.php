<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayRedirect;
use App\Payments\PaymentGatewayRegistry;
use App\Payments\PaymentGatewayVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGatewayAbstractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_model_and_gateway_registry_contract(): void
    {
        $payment = Payment::factory()->create(['amount' => 750_000]);
        $payment->markPaid('REF123', ['status' => 'ok']);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('REF123', $payment->fresh()->gateway_ref_id);
        $this->assertSame('ok', $payment->fresh()->gateway_response['status']);

        $gateway = new class implements PaymentGateway {
            public function key(): string { return 'fake'; }
            public function createPayment(Payment $payment): PaymentGatewayRedirect
            {
                return new PaymentGatewayRedirect('AUTH123', 'https://gateway.test/AUTH123');
            }
            public function verifyPayment(Payment $payment, array $payload): PaymentGatewayVerification
            {
                return new PaymentGatewayVerification(true, 'REF123', $payload);
            }
        };

        $registry = new PaymentGatewayRegistry([$gateway]);

        $this->assertSame('AUTH123', $registry->get('fake')->createPayment($payment)->authority);
        $this->assertTrue($registry->get('fake')->verifyPayment($payment, [])->paid);
    }
}

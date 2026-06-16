<?php

namespace App\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    public function key(): string;

    public function createPayment(Payment $payment): PaymentGatewayRedirect;

    /**
     * @param array<string, mixed> $payload
     */
    public function verifyPayment(Payment $payment, array $payload): PaymentGatewayVerification;
}

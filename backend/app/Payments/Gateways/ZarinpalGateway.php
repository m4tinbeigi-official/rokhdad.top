<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayRedirect;
use App\Payments\PaymentGatewayVerification;
use Illuminate\Support\Facades\Http;

class ZarinpalGateway implements PaymentGateway
{
    private string $merchantId;

    private bool $sandbox;

    public function __construct()
    {
        $this->merchantId = (string) config('services.zarinpal.merchant_id', '');
        $this->sandbox = (bool) config('services.zarinpal.sandbox', true);
    }

    public function key(): string
    {
        return 'zarinpal';
    }

    public function createPayment(Payment $payment): PaymentGatewayRedirect
    {
        $baseUrl = $this->sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://payment.zarinpal.com/pg/v4/payment';

        $response = Http::timeout(15)->post("{$baseUrl}/request.json", [
            'merchant_id' => $this->merchantId,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'callback_url' => $payment->callback_url,
            'description' => "پرداخت بلیت رویداد - #{$payment->registration_id}",
            'metadata' => [
                'payment_id' => $payment->id,
                'registration_id' => $payment->registration_id,
            ],
        ]);

        $body = $response->json();

        if (! $response->successful() || ($body['data']['code'] ?? null) !== 100) {
            $errorCode = $body['errors']['code'] ?? $body['data']['code'] ?? 'unknown';
            throw new \RuntimeException("ZarinPal createPayment failed. Code: {$errorCode}");
        }

        $authority = $body['data']['authority'];

        $gatewayBase = $this->sandbox
            ? 'https://sandbox.zarinpal.com/pg/StartPay'
            : 'https://www.zarinpal.com/pg/StartPay';

        $redirectUrl = "{$gatewayBase}/{$authority}";

        return new PaymentGatewayRedirect(
            authority: $authority,
            redirectUrl: $redirectUrl,
            rawResponse: $body,
        );
    }

    public function verifyPayment(Payment $payment, array $payload): PaymentGatewayVerification
    {
        $status = $payload['Status'] ?? $payload['status'] ?? null;

        // User cancelled or payment was not successful
        if ($status !== 'OK') {
            return new PaymentGatewayVerification(
                paid: false,
                rawResponse: $payload,
            );
        }

        $baseUrl = $this->sandbox
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://payment.zarinpal.com/pg/v4/payment';

        $response = Http::timeout(15)->post("{$baseUrl}/verify.json", [
            'merchant_id' => $this->merchantId,
            'amount' => $payment->amount,
            'authority' => $payment->gateway_authority,
        ]);

        $body = $response->json();
        $code = $body['data']['code'] ?? null;

        // 100 = verified, 101 = already verified
        if (! in_array($code, [100, 101], true)) {
            return new PaymentGatewayVerification(
                paid: false,
                rawResponse: $body,
            );
        }

        $refId = (string) ($body['data']['ref_id'] ?? $body['data']['reference_id'] ?? '');

        return new PaymentGatewayVerification(
            paid: true,
            refId: $refId,
            rawResponse: $body,
        );
    }
}

<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Payments\PaymentGateway;
use App\Payments\PaymentGatewayRedirect;
use App\Payments\PaymentGatewayVerification;
use Illuminate\Support\Facades\Http;

class ZibalGateway implements PaymentGateway
{
    private string $merchant;

    public function __construct()
    {
        $this->merchant = (string) config('services.zibal.merchant', 'zibal');
    }

    public function key(): string
    {
        return 'zibal';
    }

    public function createPayment(Payment $payment): PaymentGatewayRedirect
    {
        $response = Http::timeout(15)->post('https://gateway.zibal.ir/v1/request', [
            'merchant' => $this->merchant,
            'amount' => $payment->amount,
            'callbackUrl' => $payment->callback_url,
            'description' => "پرداخت بلیت رویداد - #{$payment->registration_id}",
            'orderId' => (string) $payment->id,
        ]);

        $body = $response->json();
        $result = $body['result'] ?? null;

        // result 100 = success
        if (! $response->successful() || $result !== 100) {
            throw new \RuntimeException("Zibal createPayment failed. Result: {$result}");
        }

        $trackId = (string) ($body['trackId'] ?? '');
        $redirectUrl = "https://gateway.zibal.ir/start/{$trackId}";

        return new PaymentGatewayRedirect(
            authority: $trackId,
            redirectUrl: $redirectUrl,
            rawResponse: $body,
        );
    }

    public function verifyPayment(Payment $payment, array $payload): PaymentGatewayVerification
    {
        $status = (int) ($payload['status'] ?? 0);

        // status 1 = paid and waiting for verify, -1 = cancelled by user
        if ($status !== 1) {
            return new PaymentGatewayVerification(
                paid: false,
                rawResponse: $payload,
            );
        }

        $response = Http::timeout(15)->post('https://gateway.zibal.ir/v1/verify', [
            'merchant' => $this->merchant,
            'trackId' => $payment->gateway_authority,
        ]);

        $body = $response->json();
        $result = $body['result'] ?? null;

        // result 100 = verified, 201 = already verified
        if (! in_array($result, [100, 201], true)) {
            return new PaymentGatewayVerification(
                paid: false,
                rawResponse: $body,
            );
        }

        $refId = (string) ($body['refNumber'] ?? '');

        return new PaymentGatewayVerification(
            paid: true,
            refId: $refId,
            rawResponse: $body,
        );
    }
}

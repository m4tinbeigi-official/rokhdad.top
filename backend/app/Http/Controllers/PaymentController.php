<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Payments\PaymentGatewayRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(private PaymentGatewayRegistry $registry) {}

    /**
     * Initiate a payment for a registration.
     * POST /api/v1/registrations/{id}/pay
     */
    public function initiate(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var Registration $registration */
        $registration = Registration::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($registration->total_amount <= 0) {
            return response()->json([
                'message' => 'This registration is free and does not require payment.',
            ], 422);
        }

        if ($registration->payment_status === 'paid') {
            return response()->json([
                'message' => 'This registration is already paid.',
            ], 422);
        }

        $data = $request->validate([
            'gateway' => ['required', 'string', 'in:zarinpal,zibal'],
        ]);

        $callbackUrl = rtrim(config('app.url'), '/').'/api/v1/payments/callback/'.$data['gateway'];

        /** @var Payment $payment */
        $payment = Payment::query()->create([
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'gateway' => $data['gateway'],
            'status' => 'pending',
            'amount' => $registration->total_amount,
            'currency' => $registration->currency,
            'callback_url' => $callbackUrl,
        ]);

        $gateway = $this->registry->get($data['gateway']);
        $redirect = $gateway->createPayment($payment);

        $payment->update(['gateway_authority' => $redirect->authority]);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'redirect_url' => $redirect->redirectUrl,
                'authority' => $redirect->authority,
            ],
        ]);
    }

    /**
     * Handle gateway callback after user returns from payment page.
     * GET /api/v1/payments/callback/{gateway}
     */
    public function callback(Request $request, string $gatewayKey): RedirectResponse|JsonResponse
    {
        $payload = $request->all();

        // Resolve authority/trackId from query params (varies by gateway)
        $authority = $payload['Authority'] ?? $payload['authority'] ?? $payload['trackId'] ?? null;

        if (! $authority) {
            return response()->json(['message' => 'Invalid callback payload.'], 400);
        }

        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('gateway', $gatewayKey)
            ->where('gateway_authority', $authority)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            return response()->json(['message' => 'Payment record not found.'], 404);
        }

        $gateway = $this->registry->get($gatewayKey);
        $verification = $gateway->verifyPayment($payment, $payload);

        if ($verification->paid) {
            $payment->markPaid($verification->refId ?? '', $verification->rawResponse);

            // Update registration payment status
            $payment->registration()->update(['payment_status' => 'paid']);
            $payment->registration->confirm();

            // Redirect frontend to success page
            $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

            return redirect("{$frontendUrl}/payment/success?ref={$verification->refId}");
        }

        $payment->markFailed($verification->rawResponse);

        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return redirect("{$frontendUrl}/payment/failed?payment_id={$payment->id}");
    }

    /**
     * Get payment status.
     * GET /api/v1/payments/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var Payment $payment */
        $payment = Payment::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $payment->id,
                'gateway' => $payment->gateway,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'gateway_ref_id' => $payment->gateway_ref_id,
                'paid_at' => $payment->paid_at?->toJSON(),
                'registration_id' => $payment->registration_id,
            ],
        ]);
    }
}

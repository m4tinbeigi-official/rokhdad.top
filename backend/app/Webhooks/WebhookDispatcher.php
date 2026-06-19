<?php

namespace App\Webhooks;

use App\Models\WebhookDelivery;
use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;

class WebhookDispatcher
{
    /**
     * @param array<string, mixed> $payload
     */
    public function dispatchForOrganizer(int $organizerId, string $eventName, array $payload): void
    {
        $subscriptions = WebhookSubscription::query()
            ->where('organizer_id', $organizerId)
            ->where('is_active', true)
            ->get()
            ->filter(fn (WebhookSubscription $subscription) => $subscription->listensTo($eventName));

        foreach ($subscriptions as $subscription) {
            $deliveryPayload = [
                'event' => $eventName,
                'occurred_at' => now()->toISOString(),
                'data' => $payload,
            ];

            $signature = 'sha256=' . hash_hmac('sha256', json_encode($deliveryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $subscription->secret);

            $delivery = $subscription->deliveries()->create([
                'event_name' => $eventName,
                'status' => 'pending',
                'attempt_count' => 1,
                'signature' => $signature,
                'payload' => $deliveryPayload,
            ]);

            $this->sendDelivery($subscription, $delivery);
        }
    }

    private function sendDelivery(WebhookSubscription $subscription, WebhookDelivery $delivery): void
    {
        $response = Http::timeout(10)
            ->acceptJson()
            ->withHeaders([
                'X-Rokhdad-Event' => $delivery->event_name,
                'X-Rokhdad-Delivery' => (string) $delivery->id,
                'X-Rokhdad-Signature' => (string) $delivery->signature,
            ])
            ->post($subscription->target_url, $delivery->payload);

        $success = $response->successful();

        $delivery->update([
            'status' => $success ? 'delivered' : 'failed',
            'response_status' => $response->status(),
            'response_body' => mb_substr($response->body(), 0, 5000),
            'delivered_at' => $success ? now() : null,
            'failed_at' => $success ? null : now(),
        ]);

        $subscription->update([
            'last_delivered_at' => $success ? now() : $subscription->last_delivered_at,
            'last_failed_at' => $success ? $subscription->last_failed_at : now(),
        ]);
    }
}

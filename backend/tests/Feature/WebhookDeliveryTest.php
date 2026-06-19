<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Webhooks\WebhookEventCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creation_dispatches_webhook(): void
    {
        Http::fake([
            'https://example.com/hooks/registrations' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'is_internal' => true,
            'registration_open' => true,
            'status' => 'published',
        ]);
        WebhookSubscription::factory()->create([
            'organizer_id' => $organizer->id,
            'target_url' => 'https://example.com/hooks/registrations',
            'subscribed_events' => [WebhookEventCatalog::REGISTRATION_CREATED],
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/registrations")
            ->assertCreated();

        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hooks/registrations'
            && $request['event'] === WebhookEventCatalog::REGISTRATION_CREATED
            && $request['data']['event']['slug'] === $event->slug);

        $this->assertDatabaseHas('webhook_deliveries', [
            'event_name' => WebhookEventCatalog::REGISTRATION_CREATED,
            'status' => 'delivered',
            'response_status' => 200,
        ]);
    }

    public function test_paid_payment_dispatches_webhook(): void
    {
        config(['services.zarinpal.merchant_id' => 'test-merchant']);
        Http::fake([
            'sandbox.zarinpal.com/pg/v4/payment/verify.json' => Http::response([
                'data' => ['code' => 100, 'ref_id' => 'REFHOOK'],
                'errors' => [],
            ]),
            'https://example.com/hooks/payments' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();
        $organizer = Organizer::factory()->create();
        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'is_internal' => true,
        ]);
        $registration = Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'quantity' => 1,
            'total_amount' => 250_000,
            'currency' => 'IRR',
        ]);
        Payment::query()->create([
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'gateway' => 'zarinpal',
            'gateway_authority' => 'AUTHWEBHOOK',
            'status' => 'pending',
            'amount' => 250_000,
            'currency' => 'IRR',
            'callback_url' => 'https://rokhdad.test/api/v1/payments/callback/zarinpal',
        ]);
        WebhookSubscription::factory()->create([
            'organizer_id' => $organizer->id,
            'target_url' => 'https://example.com/hooks/payments',
            'subscribed_events' => [WebhookEventCatalog::PAYMENT_PAID],
        ]);

        $this->get('/api/v1/payments/callback/zarinpal?Authority=AUTHWEBHOOK&Status=OK')
            ->assertRedirect();

        Http::assertSent(fn ($request) => $request->url() === 'https://example.com/hooks/payments'
            && $request['event'] === WebhookEventCatalog::PAYMENT_PAID
            && $request['data']['payment']['gateway_ref_id'] === 'REFHOOK');

        $this->assertDatabaseHas('webhook_deliveries', [
            'event_name' => WebhookEventCatalog::PAYMENT_PAID,
            'status' => 'delivered',
            'response_status' => 200,
        ]);
    }
}

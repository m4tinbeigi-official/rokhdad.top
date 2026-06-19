<?php

namespace Tests\Feature;

use App\Models\Organizer;
use App\Models\User;
use App\Models\WebhookSubscription;
use App\Webhooks\WebhookEventCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookSubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_manage_owned_webhook_subscriptions(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->create();
        $organizer->users()->attach($user->id, ['role' => 'owner', 'accepted_at' => now()]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/webhook-subscriptions', [
                'organizer_id' => $organizer->id,
                'name' => 'CRM Sink',
                'target_url' => 'https://example.com/hooks/rokhdad',
                'subscribed_events' => [
                    WebhookEventCatalog::REGISTRATION_CREATED,
                    WebhookEventCatalog::PAYMENT_PAID,
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'CRM Sink')
            ->assertJsonPath('data.organizer.slug', $organizer->slug)
            ->assertJsonPath('data.subscribed_events.0', WebhookEventCatalog::REGISTRATION_CREATED);

        /** @var WebhookSubscription $subscription */
        $subscription = WebhookSubscription::query()->firstOrFail();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/me/webhook-subscriptions/{$subscription->id}", [
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/webhook-subscriptions')
            ->assertOk()
            ->assertJsonPath('data.0.id', $subscription->id)
            ->assertJsonPath('meta.available_events.0', WebhookEventCatalog::REGISTRATION_CREATED);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/webhook-subscriptions/{$subscription->id}")
            ->assertNoContent();
    }

    public function test_user_cannot_create_webhook_for_other_organizer(): void
    {
        $user = User::factory()->create();
        $organizer = Organizer::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/webhook-subscriptions', [
                'organizer_id' => $organizer->id,
                'name' => 'Blocked',
                'target_url' => 'https://example.com/hooks/blocked',
                'subscribed_events' => [WebhookEventCatalog::REGISTRATION_CREATED],
            ])
            ->assertStatus(422);
    }
}

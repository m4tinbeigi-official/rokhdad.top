<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\WebhookSubscription;
use App\Webhooks\WebhookEventCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookSubscription>
 */
class WebhookSubscriptionFactory extends Factory
{
    protected $model = WebhookSubscription::class;

    public function definition(): array
    {
        return [
            'organizer_id' => Organizer::factory(),
            'name' => 'Webhook ' . fake()->word(),
            'target_url' => fake()->url(),
            'secret' => Str::random(40),
            'subscribed_events' => [WebhookEventCatalog::REGISTRATION_CREATED],
            'is_active' => true,
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\WebhookSubscription;
use App\Webhooks\WebhookEventCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WebhookSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $subscriptions = WebhookSubscription::query()
            ->with(['organizer:id,name,slug', 'deliveries' => fn ($query) => $query->latest()->limit(5)])
            ->whereIn('organizer_id', $request->user()->organizers()->pluck('organizers.id'))
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $subscriptions->map(fn (WebhookSubscription $subscription) => $this->serializeSubscription($subscription))->values(),
            'meta' => [
                'available_events' => WebhookEventCatalog::all(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organizerIds = $request->user()->organizers()->pluck('organizers.id')->all();

        $data = $request->validate([
            'organizer_id' => ['required', 'integer', 'in:' . implode(',', $organizerIds)],
            'name' => ['required', 'string', 'max:120'],
            'target_url' => ['required', 'url', 'max:500'],
            'subscribed_events' => ['required', 'array', 'min:1'],
            'subscribed_events.*' => ['string', 'in:' . implode(',', WebhookEventCatalog::all())],
            'secret' => ['nullable', 'string', 'min:16', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $subscription = WebhookSubscription::query()->create([
            'organizer_id' => $data['organizer_id'],
            'name' => $data['name'],
            'target_url' => $data['target_url'],
            'subscribed_events' => array_values(array_unique($data['subscribed_events'])),
            'secret' => $data['secret'] ?? Str::random(40),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json([
            'data' => $this->serializeSubscription($subscription->load('organizer')),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subscription = $this->ownedSubscription($request, $id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'target_url' => ['sometimes', 'url', 'max:500'],
            'subscribed_events' => ['sometimes', 'array', 'min:1'],
            'subscribed_events.*' => ['string', 'in:' . implode(',', WebhookEventCatalog::all())],
            'secret' => ['sometimes', 'string', 'min:16', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('subscribed_events', $data)) {
            $data['subscribed_events'] = array_values(array_unique($data['subscribed_events']));
        }

        $subscription->update($data);

        return response()->json([
            'data' => $this->serializeSubscription($subscription->fresh()->load(['organizer', 'deliveries' => fn ($query) => $query->latest()->limit(5)])),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $subscription = $this->ownedSubscription($request, $id);
        $subscription->delete();

        return response()->json(status: 204);
    }

    private function ownedSubscription(Request $request, int $id): WebhookSubscription
    {
        return WebhookSubscription::query()
            ->where('id', $id)
            ->whereIn('organizer_id', $request->user()->organizers()->pluck('organizers.id'))
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSubscription(WebhookSubscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'name' => $subscription->name,
            'target_url' => $subscription->target_url,
            'subscribed_events' => $subscription->subscribed_events ?? [],
            'is_active' => $subscription->is_active,
            'secret' => $subscription->secret,
            'last_delivered_at' => $subscription->last_delivered_at?->toISOString(),
            'last_failed_at' => $subscription->last_failed_at?->toISOString(),
            'organizer' => $subscription->organizer ? [
                'id' => $subscription->organizer->id,
                'name' => $subscription->organizer->name,
                'slug' => $subscription->organizer->slug,
            ] : null,
            'recent_deliveries' => $subscription->relationLoaded('deliveries')
                ? $subscription->deliveries->map(fn ($delivery) => [
                    'id' => $delivery->id,
                    'event_name' => $delivery->event_name,
                    'status' => $delivery->status,
                    'response_status' => $delivery->response_status,
                    'delivered_at' => $delivery->delivered_at?->toISOString(),
                    'failed_at' => $delivery->failed_at?->toISOString(),
                ])->values()
                : [],
        ];
    }
}

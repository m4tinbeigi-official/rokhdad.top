<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Event;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::query()
            ->with(['organizer:id,name,slug', 'event:id,title,slug'])
            ->whereIn('organizer_id', $request->user()->organizers()->pluck('organizers.id'))
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $campaigns->map(fn (Campaign $campaign) => $this->serializeCampaign($campaign))->values(),
            'meta' => [
                'available_channels' => ['email', 'sms'],
                'available_audiences' => ['all_registrations', 'confirmed_only', 'pending_only'],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $organizerIds = $request->user()->organizers()->pluck('organizers.id')->all();

        $data = $request->validate([
            'organizer_id' => ['required', 'integer', 'in:' . implode(',', $organizerIds)],
            'event_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'channel' => ['required', 'string', 'in:email,sms'],
            'audience_type' => ['required', 'string', 'in:all_registrations,confirmed_only,pending_only'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $event = $this->resolveOwnedEvent($data['event_id'] ?? null, (int) $data['organizer_id']);

        $campaign = Campaign::query()->create([
            ...$data,
            'event_id' => $event?->id,
            'status' => 'draft',
        ]);

        return response()->json([
            'data' => $this->serializeCampaign($campaign->load(['organizer', 'event'])),
        ], 201);
    }

    public function sendSimulation(Request $request, int $id, NotificationService $notifications): JsonResponse
    {
        $campaign = Campaign::query()
            ->with(['organizer', 'event'])
            ->where('id', $id)
            ->whereIn('organizer_id', $request->user()->organizers()->pluck('organizers.id'))
            ->firstOrFail();

        $registrations = Registration::query()
            ->with('user')
            ->whereHas('event', function ($query) use ($campaign) {
                $query->where('organizer_id', $campaign->organizer_id);

                if ($campaign->event_id) {
                    $query->where('id', $campaign->event_id);
                }
            })
            ->when($campaign->audience_type === 'confirmed_only', fn ($query) => $query->where('status', 'confirmed'))
            ->when($campaign->audience_type === 'pending_only', fn ($query) => $query->where('status', 'pending'))
            ->get();

        $recipients = $registrations->pluck('user')->filter()->unique('id')->values();
        $sentCount = 0;

        /** @var User $user */
        foreach ($recipients as $user) {
            if ($campaign->channel === 'email') {
                if (! $user->email) {
                    continue;
                }

                $notifications->sendEmail(
                    $user->email,
                    $user->name ?? 'کاربر رخداد',
                    $campaign->subject ?: $campaign->name,
                    'campaign-message',
                    [
                        'name' => $user->name ?? 'کاربر رخداد',
                        'message' => $campaign->message,
                    ],
                    'campaign_simulation',
                    $user->id,
                );
                $sentCount++;
                continue;
            }

            if (! $user->phone_e164) {
                continue;
            }

            $notifications->sendSms(
                [$user->phone_e164],
                $campaign->message,
                'campaign_simulation',
                $user->id,
            );
            $sentCount++;
        }

        $campaign->update([
            'status' => 'simulated',
            'recipients_count' => $recipients->count(),
            'sent_count' => $sentCount,
            'last_sent_at' => now(),
            'metadata' => [
                ...($campaign->metadata ?? []),
                'last_simulation' => [
                    'recipients_count' => $recipients->count(),
                    'sent_count' => $sentCount,
                ],
            ],
        ]);

        return response()->json([
            'data' => $this->serializeCampaign($campaign->fresh()->load(['organizer', 'event'])),
        ]);
    }

    private function resolveOwnedEvent(?int $eventId, int $organizerId): ?Event
    {
        if (! $eventId) {
            return null;
        }

        return Event::query()
            ->where('id', $eventId)
            ->where('organizer_id', $organizerId)
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCampaign(Campaign $campaign): array
    {
        return [
            'id' => $campaign->id,
            'name' => $campaign->name,
            'channel' => $campaign->channel,
            'audience_type' => $campaign->audience_type,
            'status' => $campaign->status,
            'subject' => $campaign->subject,
            'message' => $campaign->message,
            'recipients_count' => $campaign->recipients_count,
            'sent_count' => $campaign->sent_count,
            'last_sent_at' => $campaign->last_sent_at?->toISOString(),
            'organizer' => $campaign->organizer ? [
                'id' => $campaign->organizer->id,
                'name' => $campaign->organizer->name,
                'slug' => $campaign->organizer->slug,
            ] : null,
            'event' => $campaign->event ? [
                'id' => $campaign->event->id,
                'title' => $campaign->event->title,
                'slug' => $campaign->event->slug,
            ] : null,
        ];
    }
}

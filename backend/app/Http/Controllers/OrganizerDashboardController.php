<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Campaign;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrganizerDashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $organizers = $user->organizers()
            ->withCount(['events'])
            ->orderBy('name')
            ->get();

        $organizerIds = $organizers->pluck('id');

        $eventStats = Event::query()
            ->withCount([
                'registrations',
                'registrations as confirmed_registrations_count' => fn ($query) => $query->where('status', 'confirmed'),
                'tickets',
            ])
            ->withSum('registrations as paid_revenue', 'total_amount')
            ->whereIn('organizer_id', $organizerIds)
            ->get();

        $events = Event::query()
            ->with(['category:id,name,slug', 'city:id,name,slug', 'organizer:id,name,slug'])
            ->withCount([
                'registrations',
                'registrations as confirmed_registrations_count' => fn ($query) => $query->where('status', 'confirmed'),
                'tickets',
            ])
            ->withSum('registrations as paid_revenue', 'total_amount')
            ->whereIn('organizer_id', $organizerIds)
            ->latest('starts_at')
            ->limit(8)
            ->get();

        $registrations = Registration::query()
            ->with('event:id,organizer_id,event_type,title,slug')
            ->whereHas('event', fn ($query) => $query->whereIn('organizer_id', $organizerIds))
            ->get();
        $campaigns = Campaign::query()
            ->with(['organizer:id,name,slug', 'event:id,title,slug'])
            ->whereIn('organizer_id', $organizerIds)
            ->latest()
            ->limit(6)
            ->get();

        return response()->json([
            'data' => [
                'summary' => [
                    'organizers_count' => $organizers->count(),
                    'events_count' => (int) $organizers->sum('events_count'),
                    'registrations_count' => (int) $eventStats->sum('registrations_count'),
                    'confirmed_registrations_count' => (int) $eventStats->sum('confirmed_registrations_count'),
                    'tickets_count' => (int) $eventStats->sum('tickets_count'),
                    'revenue_total' => (int) $eventStats->sum('paid_revenue'),
                    'currency' => 'IRR',
                    'conversion_rate' => $eventStats->sum('registrations_count') > 0
                        ? round(($eventStats->sum('confirmed_registrations_count') / $eventStats->sum('registrations_count')) * 100, 1)
                        : 0,
                    'avg_revenue_per_registration' => $eventStats->sum('registrations_count') > 0
                        ? (int) round($eventStats->sum('paid_revenue') / $eventStats->sum('registrations_count'))
                        : 0,
                ],
                'analytics' => [
                    'registration_funnel' => [
                        'pending' => (int) $registrations->where('status', 'pending')->count(),
                        'confirmed' => (int) $registrations->where('status', 'confirmed')->count(),
                        'cancelled' => (int) $registrations->where('status', 'cancelled')->count(),
                    ],
                    'registrations_timeline' => collect(range(13, 0))
                        ->map(function (int $daysAgo) use ($registrations) {
                            $day = Carbon::now()->subDays($daysAgo);
                            $count = $registrations->filter(fn (Registration $registration) => $registration->created_at?->isSameDay($day))->count();

                            return [
                                'date' => $day->toDateString(),
                                'registrations_count' => $count,
                            ];
                        })
                        ->push([
                            'date' => Carbon::now()->toDateString(),
                            'registrations_count' => $registrations->filter(fn (Registration $registration) => $registration->created_at?->isSameDay(Carbon::now()))->count(),
                        ])->values(),
                    'event_type_breakdown' => collect(['in_person', 'online', 'hybrid'])
                        ->map(fn (string $type) => [
                            'event_type' => $type,
                            'events_count' => (int) $eventStats->where('event_type', $type)->count(),
                            'registrations_count' => (int) $eventStats->where('event_type', $type)->sum('registrations_count'),
                            'revenue_total' => (int) $eventStats->where('event_type', $type)->sum('paid_revenue'),
                        ])->values(),
                    'top_events' => $eventStats
                        ->sortByDesc('paid_revenue')
                        ->take(5)
                        ->map(fn (Event $event) => [
                            'id' => $event->id,
                            'title' => $event->title,
                            'slug' => $event->slug,
                            'registrations_count' => (int) $event->registrations_count,
                            'confirmed_registrations_count' => (int) $event->confirmed_registrations_count,
                            'revenue_total' => (int) ($event->paid_revenue ?? 0),
                        ])->values(),
                ],
                'campaigns' => $campaigns->map(fn (Campaign $campaign) => [
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
                    'organizer' => $campaign->organizer,
                    'event' => $campaign->event,
                ])->values(),
                'organizers' => $organizers->map(fn ($organizer) => [
                    'id' => $organizer->id,
                    'name' => $organizer->name,
                    'slug' => $organizer->slug,
                    'role' => $organizer->pivot->role,
                    'events_count' => $organizer->events_count,
                ])->values(),
                'events' => $events->map(fn (Event $event) => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'slug' => $event->slug,
                    'status' => $event->status,
                    'is_internal' => $event->is_internal,
                    'starts_at' => $event->starts_at?->toISOString(),
                    'event_type' => $event->event_type,
                    'capacity' => $event->capacity,
                    'registration_open' => $event->registration_open,
                    'registrations_count' => $event->registrations_count,
                    'confirmed_registrations_count' => $event->confirmed_registrations_count,
                    'tickets_count' => $event->tickets_count,
                    'revenue_total' => (int) ($event->paid_revenue ?? 0),
                    'category' => $event->category,
                    'city' => $event->city,
                    'organizer' => $event->organizer,
                ])->values(),
            ],
        ]);
    }
}

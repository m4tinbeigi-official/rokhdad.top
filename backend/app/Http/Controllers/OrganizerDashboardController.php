<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
                ],
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

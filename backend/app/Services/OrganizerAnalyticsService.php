<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Support\Facades\DB;

class OrganizerAnalyticsService
{
    public static function getDashboardStats(Organizer $organizer): array
    {
        return [
            'overview' => self::getOverviewStats($organizer),
            'revenue' => self::getRevenueStats($organizer),
            'registrations' => self::getRegistrationStats($organizer),
            'events' => self::getEventStats($organizer),
            'trends' => self::getTrendData($organizer),
        ];
    }

    private static function getOverviewStats(Organizer $organizer): array
    {
        $events = $organizer->events();
        $registrations = DB::table('registrations')
            ->whereIn('event_id', $events->pluck('id'));

        return [
            'total_events' => $events->count(),
            'active_events' => $events->where('starts_at', '>', now())->count(),
            'past_events' => $events->where('starts_at', '<', now())->count(),
            'total_registrations' => $registrations->count(),
            'pending_registrations' => $registrations->where('status', 'pending')->count(),
            'total_revenue' => DB::table('payments')
                ->join('registrations', 'payments.registration_id', '=', 'registrations.id')
                ->whereIn('registrations.event_id', $events->pluck('id'))
                ->where('payments.status', 'paid')
                ->sum('payments.amount'),
        ];
    }

    private static function getRevenueStats(Organizer $organizer): array
    {
        $eventIds = $organizer->events()->pluck('id');
        
        $byEvent = DB::table('payments')
            ->join('registrations', 'payments.registration_id', '=', 'registrations.id')
            ->join('events', 'registrations.event_id', '=', 'events.id')
            ->whereIn('registrations.event_id', $eventIds)
            ->where('payments.status', 'paid')
            ->select('events.title', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('events.id')
            ->orderByDesc('total')
            ->get();

        $byDate = DB::table('payments')
            ->join('registrations', 'payments.registration_id', '=', 'registrations.id')
            ->whereIn('registrations.event_id', $eventIds)
            ->where('payments.status', 'paid')
            ->select(
                DB::raw('DATE(payments.created_at) as date'),
                DB::raw('SUM(payments.amount) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'by_event' => $byEvent,
            'by_date' => $byDate,
        ];
    }

    private static function getRegistrationStats(Organizer $organizer): array
    {
        $eventIds = $organizer->events()->pluck('id');
        
        return [
            'by_status' => DB::table('registrations')
                ->whereIn('event_id', $eventIds)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'by_payment' => DB::table('registrations')
                ->whereIn('event_id', $eventIds)
                ->select('payment_status', DB::raw('COUNT(*) as count'))
                ->groupBy('payment_status')
                ->get()
                ->pluck('count', 'payment_status'),
        ];
    }

    private static function getEventStats(Organizer $organizer): array
    {
        $events = $organizer->events()
            ->with(['registrations', 'ratings'])
            ->get()
            ->map(fn($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'starts_at' => $event->starts_at,
                'capacity' => $event->capacity,
                'registered' => $event->registrations()->count(),
                'registration_percent' => $event->capacity ? 
                    round(($event->registrations()->count() / $event->capacity) * 100) : 0,
                'rating' => round($event->ratings()->avg('score') ?? 0, 1),
                'rating_count' => $event->ratings()->count(),
            ]);

        return [
            'list' => $events->sortByDesc('starts_at')->values(),
        ];
    }

    private static function getTrendData(Organizer $organizer): array
    {
        $eventIds = $organizer->events()->pluck('id');
        
        $registrationTrend = DB::table('registrations')
            ->whereIn('event_id', $eventIds)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'registrations_last_30_days' => $registrationTrend,
        ];
    }
}

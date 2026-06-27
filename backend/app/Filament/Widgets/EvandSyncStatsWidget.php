<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class EvandSyncStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $collectedEvents = Event::whereHas('sourceAttributions', function ($q) {
            $q->whereIn('source_key', ['evand', 'eseminar']);
        })->count();

        $collectedOrganizers = Organizer::whereIn('source_key', ['evand', 'eseminar'])->count();

        // Fetch total events/orgs from APIs with caching (1 hour)
        $totalEvandOrgs = Cache::remember('evand_total_orgs', 3600, function () {
            $response = Http::timeout(5)->get("https://api.evand.com/organizations?page=1&per_page=1");
            return $response->successful() ? ($response->json('meta.pagination.total') ?? 0) : 0;
        });

        $totalEseminarEvents = Cache::remember('eseminar_total_events', 3600, function () {
            // Eseminar API
            $baseUrl = rtrim((string) config('services.eseminar.base_url'), '/');
            $eventsPath = '/' . ltrim((string) config('services.eseminar.events_path'), '/');
            $response = Http::timeout(5)->get($baseUrl . $eventsPath . "?page=1&per_page=1");
            return $response->successful() ? ($response->json('meta.pagination.total') ?? $response->json('meta.total') ?? 0) : 0;
        });

        $totalEvandEvents = Cache::remember('evand_total_events', 3600, function () {
            $response = Http::timeout(5)->get("https://api.evand.com/events?page=1&per_page=1");
            return $response->successful() ? ($response->json('meta.pagination.total') ?? 0) : 0;
        });

        $estimatedTotalEvents = $totalEvandEvents + $totalEseminarEvents;
        
        $remainingEvents = max(0, $estimatedTotalEvents - $collectedEvents);

        return [
            Stat::make('Total Evand & Eseminar Events', number_format($estimatedTotalEvents))
                ->description('Estimated total on source platforms')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('info'),
            
            Stat::make('Collected Events', number_format($collectedEvents))
                ->description(number_format($remainingEvents) . ' remaining to sync')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('success'),
                
            Stat::make('Collected Organizers', number_format($collectedOrganizers))
                ->description("Target: ~" . number_format($totalEvandOrgs))
                ->descriptionIcon('heroicon-m-building-office')
                ->color('warning'),
        ];
    }
}

<?php

namespace App\Filament\Widgets;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\EventSource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    
    // Refresh the widget every 60 seconds automatically
    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $totalEvents = Event::count();
        $totalOrganizers = Organizer::count();
        
        $evandCount = Event::whereHas('source', function($q) {
            $q->where('source_key', 'evand');
        })->count();
        
        $eseminarCount = Event::whereHas('source', function($q) {
            $q->where('source_key', 'eseminar');
        })->count();

        // Count pending tasks in queues if we want to show "how much is left"
        // In a typical Laravel queue using database driver, we can count the jobs table
        // But since we use python workers, they pull directly from database tables or APIs.
        // We will just show active sources and total counts.
        $activeSourcesCount = EventSource::where('status', 'active')->count();

        return [
            Stat::make('تعداد کل رویدادها', number_format($totalEvents))
                ->description("ایوند: $evandCount | ایسمینار: $eseminarCount")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // Fake sparkline
                ->color('success'),
                
            Stat::make('تعداد برگزارکنندگان', number_format($totalOrganizers))
                ->description('جمع آوری شده تا این لحظه')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart([3, 5, 2, 8, 4, 10, 6]) // Fake sparkline
                ->color('primary'),

            Stat::make('سورس‌های فعال', $activeSourcesCount)
                ->description('منابع در حال جمع‌آوری اطلاعات')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}

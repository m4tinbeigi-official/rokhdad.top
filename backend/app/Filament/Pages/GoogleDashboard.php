<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\GoogleAnalyticsMetric;
use App\Models\GoogleSearchConsoleMetric;
use App\Models\GoogleSetting;
use App\Services\GoogleAnalyticsService;
use App\Services\GoogleSearchConsoleService;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class GoogleDashboard extends Page
{
    protected static ?string $navigationLabel = 'داشبورد گوگل';
    protected static ?string $title = 'داشبورد گوگل (Analytics & Search Console)';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static \UnitEnum|string|null $navigationGroup = 'گزارشات سیستم';

    protected string $view = 'filament.pages.google-dashboard';

    // Stats variables
    public int $totalSessions = 0;
    public int $totalPageviews = 0;
    public int $totalClicks = 0;
    public int $totalImpressions = 0;
    public float $avgCtr = 0.0;
    public float $avgPosition = 0.0;
    public string $lastSynced = 'هیچ‌وقت';
    public bool $isUsingDemoData = true;

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $settings = GoogleSetting::getActive();
        $this->isUsingDemoData = !$settings->isConnected();

        // Load metrics sum/average for the last 30 days
        $analyticsStats = GoogleAnalyticsMetric::query()
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $searchStats = GoogleSearchConsoleMetric::query()
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        if ($analyticsStats->isNotEmpty()) {
            $this->totalSessions = $analyticsStats->sum('sessions');
            $this->totalPageviews = $analyticsStats->sum('pageviews');
            
            $latest = $analyticsStats->sortByDesc('updated_at')->first();
            $this->lastSynced = $latest ? Carbon::parse($latest->updated_at)->timezone('Asia/Tehran')->format('Y-m-d H:i') : 'هیچ‌وقت';
        }

        if ($searchStats->isNotEmpty()) {
            $this->totalClicks = $searchStats->sum('clicks');
            $this->totalImpressions = $searchStats->sum('impressions');
            $this->avgCtr = round($searchStats->avg('ctr') ?? 0.0, 2);
            $this->avgPosition = round($searchStats->avg('position') ?? 0.0, 1);
        }
    }

    /**
     * Refresh data in real-time.
     */
    public function syncNow(): void
    {
        try {
            $analyticsService = app(GoogleAnalyticsService::class);
            $searchConsoleService = app(GoogleSearchConsoleService::class);

            $endDate = Carbon::yesterday()->format('Y-m-d');
            $startDate = Carbon::yesterday()->subDays(29)->format('Y-m-d');

            // Sync Analytics
            $analyticsData = $analyticsService->fetchMetrics($startDate, $endDate);
            foreach ($analyticsData as $date => $metrics) {
                GoogleAnalyticsMetric::updateOrCreate(
                    ['date' => $date],
                    [
                        'sessions' => $metrics['sessions'],
                        'pageviews' => $metrics['pageviews'],
                        'active_users' => $metrics['active_users'],
                        'bounce_rate' => $metrics['bounce_rate'],
                        'avg_session_duration' => $metrics['avg_session_duration'],
                    ]
                );
            }

            // Sync Search Console
            $searchConsoleData = $searchConsoleService->fetchMetrics($startDate, $endDate);
            foreach ($searchConsoleData as $date => $metrics) {
                GoogleSearchConsoleMetric::updateOrCreate(
                    ['date' => $date],
                    [
                        'clicks' => $metrics['clicks'],
                        'impressions' => $metrics['impressions'],
                        'ctr' => $metrics['ctr'],
                        'position' => $metrics['position'],
                    ]
                );
            }

            $this->loadStats();

            // Refresh chart components
            $this->dispatch('updateChartData');

            Notification::make()
                ->title('اطلاعات گوگل با موفقیت همگام‌سازی شد.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا در همگام‌سازی اطلاعات: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\GoogleAnalyticsWidget::class,
            \App\Filament\Widgets\GoogleSearchConsoleWidget::class,
        ];
    }
}

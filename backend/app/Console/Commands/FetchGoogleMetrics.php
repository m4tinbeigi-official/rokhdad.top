<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleAnalyticsService;
use App\Services\GoogleSearchConsoleService;
use App\Models\GoogleAnalyticsMetric;
use App\Models\GoogleSearchConsoleMetric;
use Carbon\Carbon;

class FetchGoogleMetrics extends Command
{
    protected $signature = 'google:fetch-metrics {--days=30 : Number of days to fetch}';
    protected $description = 'Fetch metrics from Google Analytics and Google Search Console and store them in the database';

    public function handle(GoogleAnalyticsService $analyticsService, GoogleSearchConsoleService $searchConsoleService): int
    {
        $days = (int) $this->option('days');
        $this->info("Fetching Google metrics for the last {$days} days...");

        $endDate = Carbon::yesterday()->format('Y-m-d');
        $startDate = Carbon::yesterday()->subDays($days - 1)->format('Y-m-d');

        // Fetch Analytics
        try {
            $this->info("Querying Google Analytics from {$startDate} to {$endDate}...");
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
            $this->info("Successfully stored " . count($analyticsData) . " Analytics records.");
        } catch (\Exception $e) {
            $this->error("Analytics fetch failed: " . $e->getMessage());
        }

        // Fetch Search Console
        try {
            $this->info("Querying Google Search Console from {$startDate} to {$endDate}...");
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
            $this->info("Successfully stored " . count($searchConsoleData) . " Search Console records.");
        } catch (\Exception $e) {
            $this->error("Search Console fetch failed: " . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}

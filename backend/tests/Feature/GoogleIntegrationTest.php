<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\GoogleSetting;
use App\Models\GoogleAnalyticsMetric;
use App\Models\GoogleSearchConsoleMetric;
use App\Services\GoogleAnalyticsService;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class GoogleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_settings_helper_methods()
    {
        $settings = GoogleSetting::getActive();
        
        $this->assertFalse($settings->hasCredentials());
        $this->assertFalse($settings->isConnected());
        $this->assertTrue($settings->isTokenExpired());

        $settings->update([
            'client_id' => 'test-id',
            'client_secret' => 'test-secret',
        ]);

        $this->assertTrue($settings->hasCredentials());
        $this->assertFalse($settings->isConnected());

        $settings->update([
            'access_token' => 'test-access',
            'refresh_token' => 'test-refresh',
            'expires_in' => 3600,
            'created_at_timestamp' => time(),
        ]);

        $this->assertTrue($settings->isConnected());
        $this->assertFalse($settings->isTokenExpired());
    }

    public function test_google_analytics_simulated_data()
    {
        $service = app(GoogleAnalyticsService::class);
        $startDate = Carbon::yesterday()->subDays(6)->format('Y-m-d');
        $endDate = Carbon::yesterday()->format('Y-m-d');

        $data = $service->getSimulatedData($startDate, $endDate);

        $this->assertCount(7, $data);
        $this->assertArrayHasKey(Carbon::yesterday()->format('Y-m-d'), $data);
        
        $yesterdayData = $data[Carbon::yesterday()->format('Y-m-d')];
        $this->assertArrayHasKey('sessions', $yesterdayData);
        $this->assertArrayHasKey('pageviews', $yesterdayData);
        $this->assertArrayHasKey('active_users', $yesterdayData);
        $this->assertArrayHasKey('bounce_rate', $yesterdayData);
        $this->assertArrayHasKey('avg_session_duration', $yesterdayData);
    }

    public function test_google_search_console_simulated_data()
    {
        $service = app(GoogleSearchConsoleService::class);
        $startDate = Carbon::yesterday()->subDays(6)->format('Y-m-d');
        $endDate = Carbon::yesterday()->format('Y-m-d');

        $data = $service->getSimulatedData($startDate, $endDate);

        $this->assertCount(7, $data);
        $this->assertArrayHasKey(Carbon::yesterday()->format('Y-m-d'), $data);
        
        $yesterdayData = $data[Carbon::yesterday()->format('Y-m-d')];
        $this->assertArrayHasKey('clicks', $yesterdayData);
        $this->assertArrayHasKey('impressions', $yesterdayData);
        $this->assertArrayHasKey('ctr', $yesterdayData);
        $this->assertArrayHasKey('position', $yesterdayData);
    }

    public function test_google_fetch_metrics_artisan_command()
    {
        $this->assertDatabaseEmpty('google_analytics_metrics');
        $this->assertDatabaseEmpty('google_search_console_metrics');

        $this->artisan('google:fetch-metrics --days=7')
            ->assertExitCode(0);

        $this->assertDatabaseCount('google_analytics_metrics', 7);
        $this->assertDatabaseCount('google_search_console_metrics', 7);

        $yesterday = Carbon::yesterday()->startOfDay()->toDateTimeString();
        $this->assertDatabaseHas('google_analytics_metrics', [
            'date' => $yesterday,
        ]);
        $this->assertDatabaseHas('google_search_console_metrics', [
            'date' => $yesterday,
        ]);
    }
}

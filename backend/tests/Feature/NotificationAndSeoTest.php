<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Notifications\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationAndSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_otp_notification_is_sent_and_logged(): void
    {
        Http::fake([
            'api.sms.ir/v1/send/verify' => Http::response(['status' => 1, 'message' => 'ok']),
        ]);

        $user = User::factory()->create();
        $log = app(NotificationService::class)->sendOtp(
            $user->phone_e164,
            12345,
            [['name' => 'CODE', 'value' => '123456']],
            $user->id,
        );

        $this->assertSame('sent', $log->fresh()->status);
        $this->assertDatabaseHas('notification_logs', [
            'user_id' => $user->id,
            'channel' => 'sms',
            'provider' => 'sms.ir',
            'type' => 'otp',
            'status' => 'sent',
        ]);
    }

    public function test_pakett_email_notification_is_sent_and_logged(): void
    {
        Http::fake([
            'app.pakett.ir/api/v1/send/template' => Http::response(['id' => 'MSG123']),
        ]);

        $user = User::factory()->create();
        $log = app(NotificationService::class)->sendEmail(
            $user->email,
            $user->name,
            'Registration confirmed',
            'registration-confirmed',
            ['name' => $user->name],
            'registration_confirm',
            $user->id,
        );

        $this->assertSame('sent', $log->fresh()->status);
        $this->assertSame('MSG123', $log->fresh()->provider_message_id);
    }

    public function test_sitemap_and_robots_are_served(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
            ->assertSee("/events/{$event->slug}", false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->assertSee('Sitemap:');
    }
}

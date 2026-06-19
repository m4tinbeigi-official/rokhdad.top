<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AttendeeTransferApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_export_event_attendees_as_csv(): void
    {
        $owner = User::factory()->create();
        $attendee = User::factory()->create([
            'name' => 'Ali Attendee',
            'email' => 'ali@example.com',
            'phone_e164' => '+989121111111',
        ]);
        $organizer = Organizer::factory()->create();
        $organizer->users()->attach($owner->id, ['role' => 'owner', 'accepted_at' => now()]);
        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'is_internal' => true,
        ]);
        Registration::query()->create([
            'event_id' => $event->id,
            'user_id' => $attendee->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'quantity' => 2,
            'total_amount' => 500_000,
            'currency' => 'IRR',
            'form_data' => ['company' => 'Rokhdad'],
            'confirmed_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->get("/api/v1/me/events/{$event->id}/attendees/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('ali@example.com', $content);
        $this->assertStringContainsString('Rokhdad', $content);
    }

    public function test_organizer_can_import_attendees_from_csv(): void
    {
        $owner = User::factory()->create();
        $organizer = Organizer::factory()->create();
        $organizer->users()->attach($owner->id, ['role' => 'owner', 'accepted_at' => now()]);
        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'is_internal' => true,
        ]);

        $csv = implode("\n", [
            'name,email,phone_e164,quantity,status,payment_status,total_amount,currency,form_data_json',
            'Sara Test,sara@example.com,+989121234567,1,confirmed,free,0,IRR,"{""company"":""Acme""}"',
        ]);

        $file = UploadedFile::fake()->createWithContent('attendees.csv', $csv);

        $this->actingAs($owner, 'sanctum')
            ->post("/api/v1/me/events/{$event->id}/attendees/import", [
                'file' => $file,
            ])
            ->assertOk()
            ->assertJsonPath('data.imported_count', 1)
            ->assertJsonPath('data.skipped_count', 0);

        $user = User::query()->where('email', 'sara@example.com')->firstOrFail();

        $this->assertDatabaseHas('registrations', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'confirmed',
            'payment_status' => 'free',
            'quantity' => 1,
        ]);
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_user_cannot_transfer_attendees_for_unowned_event(): void
    {
        $owner = User::factory()->create();
        $event = Event::factory()->create();

        $this->actingAs($owner, 'sanctum')
            ->get("/api/v1/me/events/{$event->id}/attendees/export")
            ->assertNotFound();
    }
}

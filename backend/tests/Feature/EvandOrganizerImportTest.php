<?php

namespace Tests\Feature;

use App\Models\Organizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvandOrganizerImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizers_table_has_source_metadata_columns(): void
    {
        foreach (['source_key', 'external_id', 'logo_url', 'cover_url', 'metadata'] as $column) {
            $this->assertTrue(Schema::hasColumn('organizers', $column), "Missing organizers.$column");
        }
    }

    public function test_evand_import_organizers_fetches_and_enriches_organization_profiles(): void
    {
        Http::fake([
            'api.evand.com/cities' => Http::response([
                'data' => [
                    ['id' => 87, 'name' => 'تهران', 'province' => 'تهران'],
                ],
            ]),
            'api.evand.com/events*' => Http::response([
                'data' => [
                    [
                        'id' => 25021621,
                        'city_id' => 87,
                        'organization_id' => 'xj4w',
                        'organization' => [
                            'id' => 'xj4w',
                            'name' => 'آکادمی دیجیتال مارکتینگ دهبان',
                            'slug' => 'dmacourse',
                            'logo' => ['original' => 'https://static.evand.net/logo.jpg'],
                        ],
                    ],
                ],
            ]),
            'api.evand.com/organizations/dmacourse' => Http::response([
                'data' => [
                    'id' => 'xj4w',
                    'name' => 'آکادمی دیجیتال مارکتینگ دهبان',
                    'slug' => 'dmacourse',
                    'description' => '<p>آموزش تخصصی دیجیتال مارکتینگ</p>',
                    'logo' => ['original' => 'https://static.evand.net/logo.jpg'],
                    'cover' => ['original' => 'https://static.evand.net/cover.jpg'],
                    'socials' => ['instagram' => 'https://instagram.com/dmacourse'],
                ],
            ]),
        ]);

        $this->artisan('evand:import-organizers', ['--pages' => 1, '--per-page' => 1])
            ->expectsOutput('Imported 1 Evand organizers. Skipped 0 records without organization data.')
            ->assertExitCode(0);

        $organizer = Organizer::query()->where('source_key', 'evand')->where('external_id', 'xj4w')->firstOrFail();

        $this->assertSame('evand-dmacourse', $organizer->slug);
        $this->assertSame('آموزش تخصصی دیجیتال مارکتینگ', $organizer->description);
        $this->assertSame('https://static.evand.net/logo.jpg', $organizer->logo_url);
        $this->assertSame('https://static.evand.net/cover.jpg', $organizer->cover_url);
        $this->assertSame('https://instagram.com/dmacourse', $organizer->social_links['instagram']);
        $this->assertSame('dmacourse', $organizer->metadata['evand']['slug']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\EventSource;
use App\Models\EventSourceApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventSourceApiKeyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_source_api_keys_table_exists_with_core_columns(): void
    {
        foreach (['event_source_id', 'name', 'key_hash', 'encrypted_secret', 'status'] as $column) {
            $this->assertTrue(Schema::hasColumn('event_source_api_keys', $column), "Missing event_source_api_keys.$column");
        }

        $this->assertTrue(Schema::hasColumn('event_source_api_keys', 'rotated_at'));
        $this->assertTrue(Schema::hasColumn('event_source_api_keys', 'expires_at'));
    }

    public function test_api_key_issue_hashes_secret_and_hides_encrypted_value(): void
    {
        $source = EventSource::factory()->create(['source_key' => 'evand']);
        $key = EventSourceApiKey::issue($source, 'Primary', 'plain-secret');
        $raw = DB::table('event_source_api_keys')->where('id', $key->id)->first();

        $this->assertTrue($key->source->is($source));
        $this->assertTrue($key->matchesSecret('plain-secret'));
        $this->assertFalse($key->matchesSecret('wrong-secret'));
        $this->assertNotSame('plain-secret', $key->key_hash);
        $this->assertNotSame('plain-secret', $raw->encrypted_secret);
        $this->assertArrayNotHasKey('encrypted_secret', $key->toArray());
    }

    public function test_api_key_can_rotate_and_revoke(): void
    {
        $key = EventSourceApiKey::issue(EventSource::factory()->create(), 'Primary', 'old-secret');

        $key->rotate('new-secret');
        $key->refresh();

        $this->assertFalse($key->matchesSecret('old-secret'));
        $this->assertTrue($key->matchesSecret('new-secret'));
        $this->assertNotNull($key->rotated_at);
        $this->assertSame('active', $key->status);

        $key->revoke();
        $key->refresh();

        $this->assertSame('revoked', $key->status);
    }

    public function test_event_source_exposes_active_api_keys(): void
    {
        $source = EventSource::factory()->create();
        $active = EventSourceApiKey::issue($source, 'Active', 'active-secret');
        $revoked = EventSourceApiKey::issue($source, 'Revoked', 'revoked-secret');
        $revoked->revoke();

        $this->assertTrue($source->apiKeys->contains($active));
        $this->assertTrue($source->apiKeys->contains($revoked));
        $this->assertTrue($source->activeApiKeys->contains($active));
        $this->assertFalse($source->activeApiKeys->contains($revoked));
    }
}

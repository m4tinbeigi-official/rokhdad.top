<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Event;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentsRatingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_comments_endpoint_returns_approved_comments_only(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $user = User::factory()->create();

        $approved = Comment::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'body' => 'Approved comment',
            'status' => 'approved',
            'approved_at' => now(),
        ]);
        Comment::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'body' => 'Pending comment',
            'status' => 'pending',
        ]);

        $this->getJson("/api/v1/events/{$event->slug}/comments")
            ->assertOk()
            ->assertJsonPath('data.0.id', $approved->id)
            ->assertJsonCount(1, 'data');
    }

    public function test_authenticated_user_can_submit_pending_comment(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/comments", [
                'body' => 'Please moderate this comment.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('comments', [
            'event_id' => $event->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_rating_can_be_created_updated_summarized_and_removed(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/ratings", [
                'score' => 4,
                'review' => 'Useful event.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.score', 4);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/events/{$event->slug}/ratings", [
                'score' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('data.score', 5);

        $this->getJson("/api/v1/events/{$event->slug}/ratings")
            ->assertOk()
            ->assertJsonPath('data.average', 5)
            ->assertJsonPath('data.count', 1);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/events/{$event->slug}/ratings")
            ->assertOk();

        $this->assertDatabaseMissing('ratings', [
            'event_id' => $event->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_comment_moderation_helpers_change_status(): void
    {
        $comment = Comment::query()->create([
            'event_id' => Event::factory()->create()->id,
            'user_id' => User::factory()->create()->id,
            'body' => 'Needs review',
            'status' => 'pending',
        ]);

        $comment->approve();
        $this->assertSame('approved', $comment->fresh()->status);
        $this->assertNotNull($comment->fresh()->approved_at);

        $comment->reject();
        $this->assertSame('rejected', $comment->fresh()->status);
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\EventMergeProposal;
use App\Services\GeminiEventMatcher;
use Illuminate\Console\Command;

class AIDeduplicateEvents extends Command
{
    protected $signature = 'ai:deduplicate-events {--limit=10 : Number of new events to check}';
    protected $description = 'Use Gemini AI to detect and propose merging of duplicate events';

    public function handle(GeminiEventMatcher $matcher)
    {
        $limit = $this->option('limit');
        $this->info("Fetching up to {$limit} recent events to check for duplicates...");

        // Strategy: Take latest N events that haven't been proposed for merging yet.
        // And compare them against events from other sources that happen around the same time.

        // We will exclude events that are already a primary or duplicate in a pending proposal
        $excludeIds = EventMergeProposal::where('status', 'pending')
            ->get()
            ->flatMap(fn ($p) => [$p->primary_event_id, $p->duplicate_event_id])
            ->unique()
            ->toArray();

        $recentEvents = Event::orderBy('id', 'desc')
            ->whereNotIn('id', $excludeIds)
            ->limit($limit)
            ->get();

        if ($recentEvents->isEmpty()) {
            $this->info("No new events to check.");
            return;
        }

        $proposalsCreated = 0;

        foreach ($recentEvents as $event) {
            // Find potential candidates (same city, overlapping dates, but different ID)
            // For simplicity in this job, we just look for events happening within +/- 2 days
            $candidates = Event::where('id', '!=', $event->id)
                ->whereNotIn('id', $excludeIds)
                ->whereBetween('starts_at', [
                    $event->starts_at->copy()->subDays(2),
                    $event->starts_at->copy()->addDays(2)
                ])
                ->limit(5)
                ->get();

            foreach ($candidates as $candidate) {
                $this->info("Comparing Event #{$event->id} with Event #{$candidate->id}");
                
                $result = $matcher->compare($event, $candidate);

                if ($result['match'] && $result['confidence'] > 75.0) {
                    $this->info("Match found! Confidence: {$result['confidence']}%");
                    
                    // Create proposal
                    EventMergeProposal::create([
                        'primary_event_id' => min($event->id, $candidate->id), // older event is primary
                        'duplicate_event_id' => max($event->id, $candidate->id),
                        'confidence_score' => $result['confidence'],
                        'ai_reasoning' => $result['reasoning'],
                        'status' => 'pending',
                    ]);
                    
                    $proposalsCreated++;
                    
                    // Add both to exclude list for the rest of this run
                    $excludeIds[] = $event->id;
                    $excludeIds[] = $candidate->id;
                    
                    // Break out of candidate loop, move to next recent event
                    break;
                } else {
                    $this->line("No match. Confidence: {$result['confidence']}%");
                }
                
                // Sleep to avoid rate limits (e.g. 15 requests per minute for free tier)
                sleep(4);
            }
        }

        $this->info("Finished! Created {$proposalsCreated} new proposals.");
    }
}

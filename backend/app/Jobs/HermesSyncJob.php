<?php

namespace App\Jobs;

use App\Services\HermesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class HermesSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job. HermesService is resolved from the container at run time
     * (not serialized into the job payload).
     */
    public function handle(HermesService $hermes): void
    {
        if (! config('hermes.enabled')) {
            Log::info('Hermes disabled; skipping sync job.');

            return;
        }

        try {
            $connected = $hermes->testConnection();
            Log::info('Hermes sync job completed', ['connected' => $connected]);
        } catch (Throwable $e) {
            Log::error('Hermes sync job failed', ['error' => $e->getMessage()]);
        }
    }
}

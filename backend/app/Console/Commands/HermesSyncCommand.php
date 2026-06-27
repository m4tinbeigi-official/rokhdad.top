<?php

namespace App\Console\Commands;

use App\Jobs\HermesSyncJob;
use App\Services\HermesService;
use Illuminate\Console\Command;

class HermesSyncCommand extends Command
{
    protected $signature = 'hermes:sync {--now : Run the connection check immediately instead of queuing}';
    protected $description = 'Check connectivity to the Hermes knowledge-graph server';

    public function handle(): int
    {
        if (! config('hermes.enabled')) {
            $this->warn('Hermes is disabled (set HERMES_ENABLED=true to enable). Nothing to do.');

            return self::SUCCESS;
        }

        if ($this->option('now')) {
            $this->info('Checking Hermes connection...');

            $connected = app(HermesService::class)->testConnection();

            $this->line('Connection: '.($connected ? '<info>OK</info>' : '<error>FAILED</error>'));

            return $connected ? self::SUCCESS : self::FAILURE;
        }

        HermesSyncJob::dispatch();
        $this->info('Hermes sync job dispatched to queue.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Workflow\Application\OutboxProcessor;
use Illuminate\Console\Command;

class ProcessOutboxCommand extends Command
{
    protected $signature = 'outbox:process {--limit=50 : Maximum events reserved in this run}';

    protected $description = 'Process pending outbox events with retry and dead-letter handling.';

    public function handle(OutboxProcessor $processor): int
    {
        $summary = $processor->process((int) $this->option('limit'));

        $this->components->info(sprintf(
            'Outbox processed=%d published=%d retried=%d dead_lettered=%d',
            $summary['processed'],
            $summary['published'],
            $summary['retried'],
            $summary['dead_lettered'],
        ));

        return self::SUCCESS;
    }
}

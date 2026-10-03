<?php

namespace App\Workflow\Application;

use App\Models\Outbox;
use Illuminate\Support\Facades\DB;
use Throwable;

class OutboxProcessor
{
    public function __construct(
        private readonly int $maxAttempts = 5,
    ) {
    }

    /**
     * @param callable(Outbox): void|null $handler
     * @return array{processed:int,published:int,retried:int,dead_lettered:int}
     */
    public function process(int $limit = 50, ?callable $handler = null, ?string $workerId = null): array
    {
        $workerId ??= gethostname() ?: 'outbox-worker';
        $summary = [
            'processed' => 0,
            'published' => 0,
            'retried' => 0,
            'dead_lettered' => 0,
        ];

        $events = $this->reserve($limit, $workerId);

        foreach ($events as $event) {
            $summary['processed']++;

            try {
                ($handler ?? $this->defaultHandler(...))($event);

                $event->forceFill([
                    'status' => 'PUBLISHED',
                    'published_at' => now(),
                    'locked_at' => null,
                    'locked_by' => null,
                    'last_error' => null,
                ])->save();

                $summary['published']++;
            } catch (Throwable $exception) {
                $attempts = $event->attempts + 1;
                $deadLetter = $attempts >= $this->maxAttempts;

                $event->forceFill([
                    'status' => $deadLetter ? 'DEAD_LETTER' : 'PENDING',
                    'attempts' => $attempts,
                    'available_at' => $deadLetter ? $event->available_at : now()->addSeconds($this->backoffSeconds($attempts)),
                    'locked_at' => null,
                    'locked_by' => null,
                    'dead_lettered_at' => $deadLetter ? now() : null,
                    'last_error' => mb_substr($exception->getMessage(), 0, 2000),
                ])->save();

                $summary[$deadLetter ? 'dead_lettered' : 'retried']++;
            }
        }

        return $summary;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Outbox>
     */
    private function reserve(int $limit, string $workerId)
    {
        return DB::transaction(function () use ($limit, $workerId) {
            $events = Outbox::query()
                ->where('status', 'PENDING')
                ->where('available_at', '<=', now())
                ->orderBy('available_at')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            foreach ($events as $event) {
                $event->forceFill([
                    'status' => 'PROCESSING',
                    'locked_at' => now(),
                    'locked_by' => $workerId,
                ])->save();
            }

            return $events;
        });
    }

    private function defaultHandler(Outbox $event): void
    {
        logger()->info('Outbox event published.', [
            'event_id' => $event->id,
            'event_name' => $event->event_name,
            'aggregate_type' => $event->aggregate_type,
            'aggregate_id' => $event->aggregate_id,
        ]);
    }

    private function backoffSeconds(int $attempts): int
    {
        return min(3600, 2 ** max(0, $attempts - 1) * 60);
    }
}

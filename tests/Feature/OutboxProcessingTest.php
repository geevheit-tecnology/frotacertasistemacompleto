<?php

namespace Tests\Feature;

use App\Models\Outbox;
use App\Workflow\Application\OutboxProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class OutboxProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbox_processor_publishes_pending_events(): void
    {
        $event = Outbox::create([
            'event_name' => 'vehicle.created.v1',
            'aggregate_type' => 'vehicles',
            'aggregate_id' => (string) Str::uuid(),
            'payload' => ['vehicle_id' => (string) Str::uuid()],
            'idempotency_key' => 'vehicle.created.v1:test',
            'status' => 'PENDING',
            'available_at' => now()->subMinute(),
        ]);

        $summary = app(OutboxProcessor::class)->process(10);

        $this->assertSame([
            'processed' => 1,
            'published' => 1,
            'retried' => 0,
            'dead_lettered' => 0,
        ], $summary);

        $event->refresh();
        $this->assertSame('PUBLISHED', $event->status);
        $this->assertNotNull($event->published_at);
        $this->assertNull($event->locked_at);
        $this->assertNull($event->locked_by);
    }

    public function test_outbox_processor_retries_and_dead_letters_failed_events(): void
    {
        $event = Outbox::create([
            'event_name' => 'webhook.dispatch.v1',
            'aggregate_type' => 'integrations',
            'aggregate_id' => (string) Str::uuid(),
            'payload' => ['url' => 'https://example.invalid/hook'],
            'idempotency_key' => 'webhook.dispatch.v1:test',
            'status' => 'PENDING',
            'attempts' => 4,
            'available_at' => now()->subMinute(),
        ]);

        $summary = app(OutboxProcessor::class)->process(10, function (): void {
            throw new RuntimeException('Webhook rejected the event.');
        });

        $this->assertSame([
            'processed' => 1,
            'published' => 0,
            'retried' => 0,
            'dead_lettered' => 1,
        ], $summary);

        $event->refresh();
        $this->assertSame('DEAD_LETTER', $event->status);
        $this->assertSame(5, $event->attempts);
        $this->assertNotNull($event->dead_lettered_at);
        $this->assertSame('Webhook rejected the event.', $event->last_error);
    }

    public function test_ready_reports_outbox_dead_letters(): void
    {
        Outbox::create([
            'event_name' => 'integration.failed.v1',
            'aggregate_type' => 'integrations',
            'aggregate_id' => (string) Str::uuid(),
            'payload' => [],
            'idempotency_key' => 'integration.failed.v1:test',
            'status' => 'DEAD_LETTER',
            'attempts' => 5,
            'available_at' => now()->subMinute(),
            'dead_lettered_at' => now(),
        ]);

        $this->getJson('/api/ready')
            ->assertOk()
            ->assertJsonPath('outbox_dead_letter', 1);
    }
}

<?php

namespace Chrickell\Laraprints\Jobs;

use Chrickell\Laraprints\Models\LpEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string  $name,
        public ?array  $properties = null,
        public ?string $sessionId  = null,
        public ?string $visitId    = null,
        public ?int    $userId     = null,
        public ?string $domain     = null,
    ) {
        if ($connection = config('laraprints.queue.connection')) {
            $this->connection = $connection;
        }
        if ($queue = config('laraprints.queue.events_queue')) {
            $this->queue = $queue;
        }
    }

    public function handle(): void
    {
        LpEvent::create([
            'domain'     => $this->domain,
            'session_id' => $this->sessionId,
            'visit_id'   => $this->visitId,
            'user_id'    => $this->userId,
            'name'       => $this->name,
            'properties' => $this->properties,
            'occurred_at' => now(),
        ]);
    }
}

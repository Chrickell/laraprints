<?php

namespace Chrickell\Laraprints\Jobs;

use Chrickell\Laraprints\Models\Click;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $visitId,
        public ?int $userId,
        public string $element,
        public ?string $elementClass,
        public ?string $elementId,
        public ?string $elementStyle,
        public string $path,
        public ?string $domain = null,
    ) {
        if ($connection = config('laraprints.queue.connection')) {
            $this->connection = $connection;
        }
        if ($queue = config('laraprints.queue.clicks_queue')) {
            $this->queue = $queue;
        }
    }

    public function handle(): void
    {
        Click::create([
            'domain'        => $this->domain,
            'session_id'    => $this->sessionId,
            'visit_id'      => $this->visitId,
            'user_id'       => $this->userId,
            'element'       => $this->element,
            'element_class' => $this->elementClass,
            'element_id'    => $this->elementId,
            'element_style' => $this->elementStyle,
            'path'          => $this->path,
            'clicked_at'    => now(),
        ]);
    }
}

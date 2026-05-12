<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MetricCollected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $type;
    public int $collectedAt;
    public array $payload;

    public function __construct(string $type, int $collectedAt, array $payload)
    {
        $this->type        = $type;
        $this->collectedAt = $collectedAt;
        $this->payload     = $payload;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('metrics');
    }

    public function broadcastAs(): string
    {
        return 'MetricCollected';
    }
}

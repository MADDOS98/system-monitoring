<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fire-uit din ApacheLogObserver la fiecare INSERT in apache_logs,
 * indiferent de sursa (seeder, simulator, log shipper real, queue job, etc.).
 *
 * Frontend-ul foloseste evenimentul doar ca semnal de "date noi exista" si
 * isi face refresh-ul propriu cu throttle. Payload-ul e minimal — celelalte
 * date sunt re-citite de fiecare componenta din DB la refresh.
 */
class ApacheLogCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $logTime;
    public string $remoteHost;
    public int    $status;

    public function __construct(int $logTime, string $remoteHost, int $status)
    {
        $this->logTime    = $logTime;
        $this->remoteHost = $remoteHost;
        $this->status     = $status;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('apache-logs');
    }

    public function broadcastAs(): string
    {
        return 'ApacheLogCreated';
    }
}

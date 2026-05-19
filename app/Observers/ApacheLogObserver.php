<?php

namespace App\Observers;

use App\Events\ApacheLogCreated;
use App\Models\ApacheLog;

class ApacheLogObserver
{
    /**
     * Apelat de Eloquent dupa fiecare INSERT pe modelul ApacheLog,
     * indiferent de codul sursa care a facut insert-ul.
     */
    public function created(ApacheLog $log): void
    {
        try {
            ApacheLogCreated::dispatch(
                (int) $log->log_time,
                (string) $log->remote_host,
                (int) $log->status,
            );
        } catch (\Throwable $e) {
            // Reverb oprit sau inaccesibil — nu blocam insert-ul.
        }
    }
}

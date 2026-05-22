<?php

namespace App\Console\Commands;

use App\Services\Alerts\AlertEvaluator;
use Illuminate\Console\Command;

class EvaluateAlerts extends Command
{
    protected $signature   = 'alerts:evaluate';
    protected $description = 'Evalueaza regulile active si genereaza alerte pe ferestre glisante non-overlapping.';

    public function handle(AlertEvaluator $evaluator): int
    {
        $startedAt = microtime(true);

        $created = $evaluator->run();

        $elapsedMs = (int) round((microtime(true) - $startedAt) * 1000);
        $this->info("Created {$created} alerts in {$elapsedMs}ms.");

        return self::SUCCESS;
    }
}

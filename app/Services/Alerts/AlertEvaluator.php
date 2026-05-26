<?php

namespace App\Services\Alerts;

use App\Models\Alert;
use App\Models\AlertRule;
use Illuminate\Support\Collection;

class AlertEvaluator
{
    public function __construct(private MetricSampleFetcher $fetcher)
    {
    }

    public function run(?int $now = null): int
    {
        $now ??= time();
        $created = 0;

        $activeRules = AlertRule::where('is_active', true)->get();
        if ($activeRules->isEmpty()) {
            return 0;
        }

        $groups = $activeRules->groupBy(fn (AlertRule $r) => $r->metric . '|' . $r->operator);

        foreach ($groups as $rulesInGroup) {
            $created += $this->evaluateGroup($rulesInGroup, $now);
        }

        return $created;
    }

    private function evaluateGroup(Collection $rulesInGroup, int $now): int
    {
        $rulesSorted = $rulesInGroup
            ->sortByDesc(fn (AlertRule $r) => AlertRule::LEVEL_PRIORITY[$r->level])
            ->values();

        $first      = $rulesSorted->first();
        $metric     = $first->metric;
        $operator   = $first->operator;
        $windowSec  = $first->window_sec;
        $ratio      = $first->ratio;

        // Cursor-ul pleaca cu window_sec INAPOI fata de last_eval ca sa re-evalueze
        // ultima fereastra a rularii precedente. Asta prinde spike-uri care strabateau
        // granita la cron-tick-ul precedent. Duplicatele sunt blocate de UNIQUE constraint
        // (alert_rule_id, window_start, window_end) + insertOrIgnore.
        $defaultFrom = $now - 1800;
        $rawFrom = $rulesSorted->min(fn (AlertRule $r) => $r->last_evaluated_at ?? $defaultFrom);
        $from    = $rawFrom - $windowSec;
        $to      = $now;

        $created = 0;
        $cursor  = $from;

        while ($cursor + $windowSec <= $to) {
            $ws = $cursor;
            $we = $cursor + $windowSec;

            $samples = $this->fetcher->samples($metric, $ws, $we);
            if ($samples->isNotEmpty()) {
                $created += $this->evaluateWindow($rulesSorted, $samples, $ws, $we, $operator, $ratio);
            }

            $cursor += $windowSec;
        }

        // last_eval = $to (now), nu cursor — rularile urmatoare reincep
        // de la $to - window_sec, oferind overlap-ul "rolling".
        foreach ($rulesSorted as $rule) {
            $rule->last_evaluated_at = $to;
            $rule->save();
        }

        return $created;
    }

    // Outcome-uri returnate de evaluateRule pentru a separa "actual inserted" de "suppression trigger".
    private const RESULT_INSERTED  = 'inserted';   // alerta noua scrisa in DB
    private const RESULT_DUPLICATE = 'duplicate';  // UNIQUE blocked (deja prezenta) — suppression DA, count NU
    private const RESULT_ABORTED   = 'aborted';    // streak > inactive_reset_sec — fara suppression
    private const RESULT_NO_MATCH  = 'no_match';   // ratio insuficient — fara suppression

    private function evaluateWindow(
        Collection $rulesSorted,
        Collection $samples,
        int $ws,
        int $we,
        string $operator,
        float $ratio,
    ): int {
        $sampleCount = $samples->count();
        $values      = $samples->pluck('value');
        $peakValue   = $operator === '>' ? $values->max() : $values->min();

        foreach ($rulesSorted as $rule) {
            $outcome = $this->evaluateRule($rule, $samples, $ws, $we, $operator, $ratio, $sampleCount, (float) $peakValue);

            // INSERTED si DUPLICATE = regula a "tras" → suprimare grup (opreste cascada).
            // Diferenta: doar INSERTED se conteaza ca alerta noua.
            if ($outcome === self::RESULT_INSERTED) {
                return 1;
            }
            if ($outcome === self::RESULT_DUPLICATE) {
                return 0;
            }
            // ABORTED sau NO_MATCH → incercam urmatoarea regula in prioritate
        }

        return 0;
    }

    private function evaluateRule(
        AlertRule $rule,
        Collection $samples,
        int $ws,
        int $we,
        string $operator,
        float $ratio,
        int $sampleCount,
        float $peakValue,
    ): string {
        $matchedCount = 0;
        $lastActiveTs = $ws;          // ancora initiala = inceputul ferestrei
        $resetSec     = (int) $rule->inactive_reset_sec;

        foreach ($samples as $sample) {
            if ($this->satisfies($sample->value, $operator, $rule->threshold)) {
                $matchedCount++;
                $lastActiveTs = $sample->ts;
            } else {
                // Sample inactiv — verifica streak-ul
                if (($sample->ts - $lastActiveTs) >= $resetSec) {
                    return self::RESULT_ABORTED;
                }
            }
        }

        $observedRatio = $matchedCount / $sampleCount;
        if ($observedRatio < $ratio) {
            return self::RESULT_NO_MATCH;
        }

        // insertOrIgnore intoarce numarul de randuri inserate efectiv (0 daca UNIQUE blocked).
        $affected = Alert::query()->insertOrIgnore([
            'alert_rule_id'  => $rule->id,
            'level'          => $rule->level,
            'metric'         => $rule->metric,
            'threshold'      => $rule->threshold,
            'operator'       => $operator,
            'ratio_required' => $ratio,
            'ratio_observed' => round($observedRatio, 3),
            'sample_count'   => $sampleCount,
            'matched_count'  => $matchedCount,
            'peak_value'     => round($peakValue, 2),
            'window_start'   => $ws,
            'window_end'     => $we,
            'message'        => $this->formatMessage($rule, $observedRatio, $we - $ws),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $affected > 0 ? self::RESULT_INSERTED : self::RESULT_DUPLICATE;
    }

    private function satisfies(float $value, string $operator, float $threshold): bool
    {
        return match ($operator) {
            '>' => $value > $threshold,
            '<' => $value < $threshold,
        };
    }

    private function formatMessage(AlertRule $rule, float $observedRatio, int $windowSec): string
    {
        $pct = round($observedRatio * 100);
        return sprintf(
            '%s %s %s in %d%% of samples over %ds',
            strtoupper($rule->metric),
            $rule->operator,
            $rule->threshold,
            $pct,
            $windowSec,
        );
    }
}

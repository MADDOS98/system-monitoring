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

        $defaultFrom = $now - 1800;
        $from = $rulesSorted->min(fn (AlertRule $r) => $r->last_evaluated_at ?? $defaultFrom);
        $to   = $now;

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

        foreach ($rulesSorted as $rule) {
            $rule->last_evaluated_at = $to;
            $rule->save();
        }

        return $created;
    }

    private function evaluateWindow(
        Collection $rulesSorted,
        Collection $samples,
        int $ws,
        int $we,
        string $operator,
        float $ratio,
    ): int {
        $sampleCount = $samples->count();
        $peakValue   = $operator === '>' ? $samples->max() : $samples->min();

        foreach ($rulesSorted as $rule) {
            $matched       = $samples->filter(fn (float $v) => $this->satisfies($v, $operator, $rule->threshold));
            $matchedCount  = $matched->count();
            $observedRatio = $matchedCount / $sampleCount;

            if ($observedRatio >= $ratio) {
                Alert::create([
                    'alert_rule_id'  => $rule->id,
                    'level'          => $rule->level,
                    'metric'         => $rule->metric,
                    'threshold'      => $rule->threshold,
                    'operator'       => $operator,
                    'ratio_required' => $ratio,
                    'ratio_observed' => round($observedRatio, 3),
                    'sample_count'   => $sampleCount,
                    'matched_count'  => $matchedCount,
                    'peak_value'     => round((float) $peakValue, 2),
                    'window_start'   => $ws,
                    'window_end'     => $we,
                    'message'        => $this->formatMessage(
                        $rule,
                        $observedRatio,
                        $we - $ws,
                    ),
                ]);

                return 1;
            }
        }

        return 0;
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

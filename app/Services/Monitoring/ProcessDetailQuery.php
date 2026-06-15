<?php

namespace App\Services\Monitoring;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessDetailQuery
{
    public function cpuSnapshot(string $name, int $fromTs, int $toTs): array
    {
        $pid = $this->processId($name);
        $period = $this->periodLabel($fromTs, $toTs);
        $diff = max(1, $toTs - $fromTs);
        $bucket = BucketResolver::secondsForProcess($diff);
        $labelFmt = BucketResolver::labelFormat($bucket, $diff);

        if ($pid === null) {
            return $this->emptyResponse('cpu', $period, $bucket, $labelFmt);
        }

        // Stats agregate pe fereastra
        $stats = DB::connection('process_metrics')->table('process_metrics')
            ->where('process_name_id', $pid)
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->selectRaw('AVG(cpu_pct) AS avg, MAX(cpu_pct) AS max, AVG(count) AS avg_count')
            ->first();

        $latest = $this->latest($pid);

        $chartData = $this->bucketSeries($pid, $fromTs, $toTs, $bucket, $labelFmt, [
            'cpu' => 'AVG(cpu_pct)',
        ]);

        return [
            'metric'        => 'cpu',
            'latest'        => round((float) ($latest->cpu_pct ?? 0), 2),
            'avg'           => round((float) ($stats->avg ?? 0), 2),
            'peak'          => round((float) ($stats->max ?? 0), 2),
            'count'         => (int) ($latest->count ?? 0),
            'periodLabel'   => $period,
            'bucketSeconds' => $bucket,
            'labelFormat'   => $labelFmt,
            'chartData'     => [
                'labels' => $chartData['labels'],
                'cpu'    => $chartData['cpu'],
            ],
        ];
    }

    public function ramSnapshot(string $name, int $fromTs, int $toTs): array
    {
        $pid = $this->processId($name);
        $period = $this->periodLabel($fromTs, $toTs);
        $diff = max(1, $toTs - $fromTs);
        $bucket = BucketResolver::secondsForProcess($diff);
        $labelFmt = BucketResolver::labelFormat($bucket, $diff);

        if ($pid === null) {
            return $this->emptyResponse('ram', $period, $bucket, $labelFmt);
        }

        $stats = DB::connection('process_metrics')->table('process_metrics')
            ->where('process_name_id', $pid)
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->selectRaw('AVG(ram_kb) AS avg, MAX(ram_kb) AS max')
            ->first();

        $latest = $this->latest($pid);

        $chartData = $this->bucketSeries($pid, $fromTs, $toTs, $bucket, $labelFmt, [
            'ram' => 'AVG(ram_kb)',
        ]);

        return [
            'metric'        => 'ram',
            'latest'        => (int) ($latest->ram_kb ?? 0),
            'avg'           => (int) ($stats->avg ?? 0),
            'peak'          => (int) ($stats->max ?? 0),
            'count'         => (int) ($latest->count ?? 0),
            'periodLabel'   => $period,
            'bucketSeconds' => $bucket,
            'labelFormat'   => $labelFmt,
            'chartData'     => [
                'labels' => $chartData['labels'],
                'ram'    => $chartData['ram'],
            ],
        ];
    }

    public function infoSnapshot(string $name, int $fromTs, int $toTs): array
    {
        $pid = $this->processId($name);
        $period = $this->periodLabel($fromTs, $toTs);
        $diff = max(1, $toTs - $fromTs);
        $bucket = BucketResolver::secondsForProcess($diff);
        $labelFmt = BucketResolver::labelFormat($bucket, $diff);

        if ($pid === null) {
            return $this->emptyResponse('info', $period, $bucket, $labelFmt);
        }

        $stats = DB::connection('process_metrics')->table('process_metrics')
            ->where('process_name_id', $pid)
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->selectRaw('AVG(count) AS avg, MAX(count) AS max, MIN(count) AS min')
            ->first();

        $latest = $this->latest($pid);

        // Cheia in chartData se numeste 'info' pentru a fi consistenta cu identificatorul
        // de metric folosit prin URL/Livewire/JS (vezi process-chart.blade.php).
        $chartData = $this->bucketSeries($pid, $fromTs, $toTs, $bucket, $labelFmt, [
            'info' => 'AVG(count)',
        ]);

        return [
            'metric'        => 'info',
            'latest'        => (int)   ($latest->count ?? 0),
            'avg'           => round((float) ($stats->avg ?? 0), 2),
            'peak'          => (int)   ($stats->max ?? 0),
            'min'           => (int)   ($stats->min ?? 0),
            'periodLabel'   => $period,
            'bucketSeconds' => $bucket,
            'labelFormat'   => $labelFmt,
            'chartData'     => [
                'labels' => $chartData['labels'],
                'info'   => $chartData['info'],
            ],
        ];
    }

    public function diskSnapshot(string $name, int $fromTs, int $toTs): array
    {
        $pid = $this->processId($name);
        $period = $this->periodLabel($fromTs, $toTs);
        $diff = max(1, $toTs - $fromTs);
        $bucket = BucketResolver::secondsForProcess($diff);
        $labelFmt = BucketResolver::labelFormat($bucket, $diff);

        if ($pid === null) {
            return $this->emptyResponse('disk', $period, $bucket, $labelFmt);
        }

        $stats = DB::connection('process_metrics')->table('process_metrics')
            ->where('process_name_id', $pid)
            ->whereBetween('collected_at', [$fromTs, $toTs])
            ->selectRaw(
                'AVG(read_bytes) AS avg_read, MAX(read_bytes) AS peak_read,
                 AVG(write_bytes) AS avg_write, MAX(write_bytes) AS peak_write'
            )
            ->first();

        $latest = $this->latest($pid);

        $chartData = $this->bucketSeries($pid, $fromTs, $toTs, $bucket, $labelFmt, [
            'read'  => 'AVG(read_bytes)',
            'write' => 'AVG(write_bytes)',
        ]);

        return [
            'metric'        => 'disk',
            'latestRead'    => (int) ($latest->read_bytes  ?? 0),
            'latestWrite'   => (int) ($latest->write_bytes ?? 0),
            'avgRead'       => (int) ($stats->avg_read   ?? 0),
            'avgWrite'      => (int) ($stats->avg_write  ?? 0),
            'peakRead'      => (int) ($stats->peak_read  ?? 0),
            'peakWrite'     => (int) ($stats->peak_write ?? 0),
            'count'         => (int) ($latest->count ?? 0),
            'periodLabel'   => $period,
            'bucketSeconds' => $bucket,
            'labelFormat'   => $labelFmt,
            'chartData'     => [
                'labels' => $chartData['labels'],
                'read'   => $chartData['read'],
                'write'  => $chartData['write'],
            ],
        ];
    }

    private function processId(string $name): ?int
    {
        $row = DB::connection('process_metrics')->table('process_names')
            ->where('name', $name)
            ->first(['id']);
        return $row ? (int) $row->id : null;
    }

    private function latest(int $pid): ?\stdClass
    {
        // ORDER BY id DESC: pentru un proces dat, randurile sunt interleaved la fiecare
        // 16 inserari (simulator scrie toate procesele intr-un tick), deci scanarea PK
        // descendent gaseste match-ul in <= 16 randuri. Semantic identic cu collected_at
        // pentru ca id e autoincrement si scrierile sunt cronologice.
        return DB::connection('process_metrics')->table('process_metrics')
            ->where('process_name_id', $pid)
            ->orderByDesc('id')
            ->first(['cpu_pct', 'ram_kb', 'read_bytes', 'write_bytes', 'count', 'collected_at']);
    }

    /**
     * Construieste un set de serii bucketate, una per coloana SELECT.
     * Aliasele provin din cheile array-ului $selects.
     */
    private function bucketSeries(int $pid, int $from, int $to, int $bucket, string $labelFmt, array $selects): array
    {
        $tz   = config('app.timezone');
        $diff = max(1, $to - $from);
        $bucketCount = (int) ceil($diff / $bucket);

        $selectParts = ['((collected_at - ?) / ?) * ? + ? AS bucket_ts'];
        foreach ($selects as $alias => $expr) {
            $selectParts[] = "$expr AS $alias";
        }
        $selectSql = implode(', ', $selectParts);

        $rows = DB::connection('process_metrics')->table('process_metrics')
            ->selectRaw($selectSql, [$from, $bucket, $bucket, $from])
            ->where('process_name_id', $pid)
            ->whereBetween('collected_at', [$from, $to])
            ->groupBy('bucket_ts')
            ->get()
            ->keyBy('bucket_ts');

        $result = ['labels' => []];
        foreach (array_keys($selects) as $alias) {
            $result[$alias] = [];
        }

        for ($i = 0; $i < $bucketCount; $i++) {
            $ts = $from + $i * $bucket;
            $result['labels'][] = Carbon::createFromTimestamp($ts, $tz)->format($labelFmt);
            $row = $rows->get($ts);
            foreach (array_keys($selects) as $alias) {
                $result[$alias][] = $row !== null ? round((float) $row->$alias, 2) : 0;
            }
        }

        return $result;
    }

    private function periodLabel(int $from, int $to): string
    {
        $tz = config('app.timezone');
        $diff = max(0, $to - $from);
        $fmt = $diff >= 86400 ? 'Y-m-d H:i' : 'H:i';
        return Carbon::createFromTimestamp($from, $tz)->format($fmt)
            . ' – '
            . Carbon::createFromTimestamp($to, $tz)->format($fmt);
    }

    private function emptyResponse(string $metric, string $period, int $bucket, string $labelFmt): array
    {
        $base = [
            'metric'        => $metric,
            'periodLabel'   => $period,
            'bucketSeconds' => $bucket,
            'labelFormat'   => $labelFmt,
            'count'         => 0,
            'chartData'     => ['labels' => []],
        ];

        return match ($metric) {
            'cpu'  => $base + ['latest' => 0, 'avg' => 0, 'peak' => 0, 'chartData' => $base['chartData'] + ['cpu' => []]],
            'ram'  => $base + ['latest' => 0, 'avg' => 0, 'peak' => 0, 'chartData' => $base['chartData'] + ['ram' => []]],
            'info' => $base + ['latest' => 0, 'avg' => 0, 'peak' => 0, 'min' => 0, 'chartData' => $base['chartData'] + ['info' => []]],
            'disk' => $base + [
                'latestRead' => 0, 'latestWrite' => 0,
                'avgRead'    => 0, 'avgWrite'    => 0,
                'peakRead'   => 0, 'peakWrite'   => 0,
                'chartData'  => $base['chartData'] + ['read' => [], 'write' => []],
            ],
        };
    }
}

<?php

namespace App\Livewire\Dashboard;

use App\Models\Alert;
use App\Services\Monitoring\CpuMetricsQuery;
use App\Services\Monitoring\DiskMetricsQuery;
use App\Services\Monitoring\NetworkMetricsQuery;
use App\Services\Monitoring\RamMetricsQuery;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DashboardOverview extends Component
{
    public function render()
    {
        $now       = time();
        $windowSec = 300; // 5 min pentru snapshot-uri (le serveste latest din window)

        $cpu  = app(CpuMetricsQuery::class)->snapshot($now - $windowSec, $now);
        $ram  = app(RamMetricsQuery::class)->snapshot($now - $windowSec, $now);
        $disk = app(DiskMetricsQuery::class)->snapshot($now - $windowSec, $now);
        $net  = app(NetworkMetricsQuery::class)->snapshot($now - $windowSec, $now);

        // Net Mbps: rx/tx (bytes per 1s sample) × 8 / 1e6.
        $rxMbps    = (int) round(($net['rxBytes'] ?? 0) * 8 / 1_000_000);
        $txMbps    = (int) round(($net['txBytes'] ?? 0) * 8 / 1_000_000);
        $totalMbps = $rxMbps + $txMbps;

        // Processes: active = au sample in process_metrics in ultima minuta.
        $activeProcessCount = (int) DB::connection('process_metrics')
            ->table('process_metrics')
            ->where('collected_at', '>=', $now - 60)
            ->distinct()
            ->count('process_name_id');

        $totalProcessCount = (int) DB::connection('process_metrics')
            ->table('process_names')
            ->count();

        $stoppedProcessCount = max(0, $totalProcessCount - $activeProcessCount);

        // Alerts active grupate dupa level.
        $alertCountsByLevel = Alert::whereNull('read_at')
            ->selectRaw('level, COUNT(*) as cnt')
            ->groupBy('level')
            ->pluck('cnt', 'level')
            ->toArray();

        $totalActiveAlerts = (int) array_sum($alertCountsByLevel);

        // Recent alerts: ultimele 8 indiferent de read/unread.
        $recentAlerts = Alert::orderByDesc('id')->limit(8)->get();

        // Top 5 procese running dupa CPU (cu sample in ultima minuta).
        $topProcesses = $this->fetchTopProcesses($now);

        // Disk/RAM in GB pentru subtitle-uri.
        $diskUsedGb  = round((int) ($disk['usedBytes']  ?? 0) / 1073741824, 1);
        $diskTotalGb = round((int) ($disk['totalBytes'] ?? 0) / 1073741824, 1);
        $diskFreeGb  = round((int) ($disk['freeBytes']  ?? 0) / 1073741824, 1);

        $ramUsedGb   = round((int) ($ram['usedKb']  ?? 0) / 1048576, 1);
        $ramTotalGb  = round((int) ($ram['totalKb'] ?? 0) / 1048576, 1);

        return view('livewire.dashboard.dashboard-overview', [
            'cpuUsage'   => round((float) ($cpu['totalUsage'] ?? 0), 1),
            'cpuCores'   => (int) ($cpu['coreCount'] ?? 0),

            'ramUsedPct' => round((float) ($ram['usedPct'] ?? 0), 1),
            'ramUsedGb'  => $ramUsedGb,
            'ramTotalGb' => $ramTotalGb,

            'diskUsedPct' => round((float) ($disk['usedPct'] ?? 0), 1),
            'diskUsedGb'  => $diskUsedGb,
            'diskTotalGb' => $diskTotalGb,
            'diskFreeGb'  => $diskFreeGb,

            'totalMbps' => $totalMbps,
            'rxMbps'    => $rxMbps,
            'txMbps'    => $txMbps,

            'activeProcessCount'  => $activeProcessCount,
            'totalProcessCount'   => $totalProcessCount,
            'stoppedProcessCount' => $stoppedProcessCount,

            'alertCountsByLevel' => $alertCountsByLevel,
            'totalActiveAlerts'  => $totalActiveAlerts,
            'recentAlerts'       => $recentAlerts,

            'topProcesses' => $topProcesses,
        ]);
    }

    private function fetchTopProcesses(int $now)
    {
        $db = DB::connection('process_metrics');

        // Doar procesele cu sample in ultima minuta (running).
        // Folosim MAX(id) ca anchor (PK direct, semantic identic cu collected_at).
        $latest = $db->table('process_metrics')
            ->where('collected_at', '>=', $now - 60)
            ->select('process_name_id', DB::raw('MAX(id) AS max_id'))
            ->groupBy('process_name_id');

        return $db->table('process_names AS pn')
            ->joinSub($latest, 'latest', 'latest.process_name_id', '=', 'pn.id')
            ->join('process_metrics AS pm', 'pm.id', '=', 'latest.max_id')
            ->select(['pn.name', 'pm.cpu_pct', 'pm.ram_kb'])
            ->orderByDesc('pm.cpu_pct')
            ->limit(5)
            ->get();
    }
}

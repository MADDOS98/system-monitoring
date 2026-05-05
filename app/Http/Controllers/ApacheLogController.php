<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApacheLog;

class ApacheLogController extends Controller
{
    public function index()
    {
        // Top source IPs — pentru tabelul stânga jos
        // tag și hostname nu vin din DB deocamdată, rămân null
        // Top source IPs cu breakdown statusuri per IP
        $topIpsRaw = ApacheLog::selectRaw('
                remote_host as ip,
                COUNT(*) as reqs,
                SUM(bytes_sent) as total_bytes,
                MAX(log_time) as last_seen_ts,
                SUM(CASE WHEN status BETWEEN 200 AND 299 THEN 1 ELSE 0 END) as s2xx,
                SUM(CASE WHEN status BETWEEN 300 AND 399 THEN 1 ELSE 0 END) as s3xx,
                SUM(CASE WHEN status BETWEEN 400 AND 499 THEN 1 ELSE 0 END) as s4xx,
                SUM(CASE WHEN status BETWEEN 500 AND 599 THEN 1 ELSE 0 END) as s5xx
            ')
            ->groupBy('remote_host')
            ->orderByDesc('reqs')
            ->limit(15)
            ->get();

        $topIps = $topIpsRaw->map(fn($row) => (object) [
            'ip'          => $row->ip,
            'hostname'    => null,
            'tag'         => null,
            'reqs'        => $row->reqs,
            'total_bytes' => $row->total_bytes,
            's2xx'        => $row->reqs > 0 ? round($row->s2xx / $row->reqs * 100) : 0,
            's3xx'        => $row->reqs > 0 ? round($row->s3xx / $row->reqs * 100) : 0,
            's4xx'        => $row->reqs > 0 ? round($row->s4xx / $row->reqs * 100) : 0,
            's5xx'        => $row->reqs > 0 ? round($row->s5xx / $row->reqs * 100) : 0,
            'last_seen'   => $row->last_seen_ts
                ? now()->diffForHumans(\Carbon\Carbon::createFromTimestamp($row->last_seen_ts), true) . ' ago'
                : '—',
        ]);

        // Requests by status group — pentru tabelul dreapta jos
        $byStatus = ApacheLog::selectRaw('
                status,
                COUNT(*) as total,
                CASE
                    WHEN status BETWEEN 200 AND 299 THEN "2xx"
                    WHEN status BETWEEN 300 AND 399 THEN "3xx"
                    WHEN status BETWEEN 400 AND 499 THEN "4xx"
                    WHEN status BETWEEN 500 AND 599 THEN "5xx"
                    ELSE "other"
                END as `group`
            ')
            ->groupBy('status', 'group')
            ->orderBy('status')
            ->get()
            ->groupBy('group')
            ->map(fn($rows, $group) => (object) [
                'group' => $group,
                'total' => $rows->sum('total'),
            ])
            ->values();

        return view('apache_logs.index', compact('topIps', 'byStatus'));
    }
}
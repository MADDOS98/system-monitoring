<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApacheLog;
use Illuminate\Support\Facades\DB;

class ApacheLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $logs = ApacheLog::orderByDesc('log_time')->paginate(50);
        return view('apache_logs.index', [
            'logs' => $logs,
            'tableView' => 'apache_logs.partials.table-default'
        ]);
    }

    /**
     * Top 15 IPs that accessed the server, ordered by number of requests.
     */
    public function topIps()
    {
        $logs = ApacheLog::selectRaw('
            remote_host,
            COUNT(*) as total,
            SUM(bytes_sent) as total_bytes,
            MAX(log_time) as last_seen
        ')
            ->groupBy('remote_host')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        return view('apache_logs.index', [
            'logs' => $logs,
            'tableView' => 'apache_logs.partials.table-top-ips'
        ]);
    }
}

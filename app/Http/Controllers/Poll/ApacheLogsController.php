<?php

namespace App\Http\Controllers\Poll;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\ApacheLogsQuery;
use App\Services\Monitoring\BucketResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApacheLogsController extends Controller
{
    public function snapshot(Request $request, ApacheLogsQuery $q): JsonResponse
    {
        $from        = (int) $request->input('from', 0);
        $to          = (int) $request->input('to',   0);
        $page        = max(1, (int) $request->input('page', 1));
        $search      = (string) $request->input('search', '');
        $searchField = (string) $request->input('search_field', 'any');
        $tab         = (string) $request->input('tab', 'All');
        $sinceId     = (int) $request->input('since_id', 0);

        if ($from <= 0 || $to <= 0 || $to <= $from) {
            return response()->json(['error' => 'invalid time range'], 400);
        }

        // Slide live window pentru consistenta cu Livewire render.
        $diff = $to - $from;
        $isLivePreset = in_array($diff, [300, 3600, 86400], true);
        if ($isLivePreset) {
            $to   = Carbon::now()->timestamp;
            $from = $to - $diff;
        }

        $bucketSeconds = BucketResolver::secondsFor(max(1, $diff));
        $day           = Carbon::createFromTimestamp($to)->format('Y-m-d');

        $paginator = $q->paginate($from, $to, $page, $search, $searchField);

        return response()->json([
            'bucketSeconds' => $bucketSeconds,
            'window'        => ['from' => $from, 'to' => $to],
            'logs' => [
                'rows'      => $paginator->items(),
                'page'      => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total'     => $paginator->total(),
                'from'      => $paginator->firstItem(),
                'to'        => $paginator->lastItem(),
            ],
            'new_logs' => [
                'rows' => $q->newSince($sinceId),
            ],
            'top_ips' => $q->topIps($from, $to, $tab),
            'status'  => $q->byStatus($from, $to),
            'peak'    => $q->peakBins($day),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Poll;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\CpuMetricsQuery;
use App\Services\Monitoring\DiskMetricsQuery;
use App\Services\Monitoring\NetworkMetricsQuery;
use App\Services\Monitoring\RamMetricsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricsController extends Controller
{
    public function snapshot(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $from = (int) $request->input('from', 0);
        $to   = (int) $request->input('to',   0);

        if ($from <= 0 || $to <= 0 || $to <= $from) {
            return response()->json(['error' => 'invalid time range'], 400);
        }

        $data = match ($type) {
            'cpu'     => app(CpuMetricsQuery::class)->snapshot($from, $to),
            'ram'     => app(RamMetricsQuery::class)->snapshot($from, $to),
            'network' => app(NetworkMetricsQuery::class)->snapshot($from, $to),
            'disk'    => app(DiskMetricsQuery::class)->snapshot($from, $to),
            default   => null,
        };

        if ($data === null) {
            return response()->json(['error' => 'unknown type'], 400);
        }

        return response()->json($data);
    }
}

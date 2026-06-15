<?php

namespace App\Http\Controllers\Poll;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\NetworkMetricsQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectionsController extends Controller
{
    public function snapshot(Request $request, NetworkMetricsQuery $q): JsonResponse
    {
        $name = $request->string('name')->toString();
        $from = (int) $request->input('from', 0);
        $to   = (int) $request->input('to',   0);

        if ($name === '') {
            return response()->json(['error' => 'missing name'], 400);
        }
        if ($from <= 0 || $to <= 0 || $to <= $from) {
            return response()->json(['error' => 'invalid time range'], 400);
        }

        return response()->json($q->connectionSnapshot($name, $from, $to));
    }
}

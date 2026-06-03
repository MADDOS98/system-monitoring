<?php

namespace App\Http\Controllers\Poll;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\ProcessDetailQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessMetricsController extends Controller
{
    public function snapshot(Request $request): JsonResponse
    {
        $type = $request->string('type')->toString();
        $name = $request->string('name')->toString();
        $from = (int) $request->input('from', 0);
        $to   = (int) $request->input('to',   0);

        if ($from <= 0 || $to <= 0 || $to <= $from) {
            return response()->json(['error' => 'invalid time range'], 400);
        }
        if ($name === '') {
            return response()->json(['error' => 'missing process name'], 400);
        }

        $q = app(ProcessDetailQuery::class);

        $data = match ($type) {
            'cpu'   => $q->cpuSnapshot($name, $from, $to),
            'ram'   => $q->ramSnapshot($name, $from, $to),
            'disk'  => $q->diskSnapshot($name, $from, $to),
            'info'  => $q->infoSnapshot($name, $from, $to),
            default => null,
        };

        if ($data === null) {
            return response()->json(['error' => 'unknown type'], 400);
        }

        return response()->json($data);
    }
}

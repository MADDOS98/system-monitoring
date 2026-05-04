<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApacheLog;

class ApacheLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $logs = ApacheLog::orderByDesc('log_time')->paginate(50);
        return view('apache_logs.index', compact('logs'));
    }
}

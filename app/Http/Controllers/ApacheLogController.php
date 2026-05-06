<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApacheLogController extends Controller
{
    public function index()
    {
        return view('apache_logs.index');
    }
}
<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Poll\ApacheLogsController as PollApacheLogsController;
use App\Http\Controllers\Poll\MetricsController as PollMetricsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApacheLogController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/apache-logs', [ApacheLogController::class, 'index'])->middleware(['auth', 'verified'])->name('apache-logs');

Route::get('/metrics', function (\Illuminate\Http\Request $request) {
    $tab = $request->query('tab', 'cpu');
    if (!in_array($tab, ['cpu', 'ram', 'network', 'disk'], true)) {
        $tab = 'cpu';
    }
    return view('metrics.index', compact('tab'));
})->middleware(['auth', 'verified'])->name('metrics');

Route::get('/alerts', fn() => view('alerts.index'))
    ->middleware(['auth', 'verified'])
    ->name('alerts');

Route::get('/percentiles', fn() => view('percentiles.index'))
    ->middleware(['auth', 'verified'])
    ->name('percentiles');

Route::get('/processes', fn() => view('processes.index'))
    ->middleware(['auth', 'verified'])
    ->name('processes');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Polling endpoints (JSON) — apelate de poller.js la interval = bucketSeconds.
Route::middleware(['auth'])->prefix('poll')->group(function () {
    Route::get('/metrics',     [PollMetricsController::class,    'snapshot'])->name('poll.metrics');
    Route::get('/apache-logs', [PollApacheLogsController::class, 'snapshot'])->name('poll.apache-logs');
});

require __DIR__ . '/auth.php';

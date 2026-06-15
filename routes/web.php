<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Poll\ApacheLogsController as PollApacheLogsController;
use App\Http\Controllers\Poll\MetricsController as PollMetricsController;
use App\Http\Controllers\Poll\ProcessMetricsController as PollProcessMetricsController;
use App\Http\Controllers\Poll\ConnectionsController as PollConnectionsController;
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

Route::get('/network/connections', function (\Illuminate\Http\Request $request) {
    $key = (string) $request->query('key', '');
    if ($key === '') {
        return redirect()->route('metrics', ['tab' => 'network']);
    }
    return view('network.connection-show', compact('key'));
})->middleware(['auth', 'verified'])->name('network.connection');

Route::get('/alerts', fn() => view('alerts.index'))
    ->middleware(['auth', 'verified'])
    ->name('alerts');

Route::get('/percentiles', fn() => view('percentiles.index'))
    ->middleware(['auth', 'verified'])
    ->name('percentiles');

Route::get('/processes', fn() => view('processes.index'))
    ->middleware(['auth', 'verified'])
    ->name('processes');

Route::get('/settings', fn() => view('settings.index'))
    ->middleware(['auth', 'verified'])
    ->name('settings');

Route::get('/processes/{name}', function (string $name, \Illuminate\Http\Request $request) {
    $tab = $request->query('tab', 'info');
    if (! in_array($tab, ['info', 'cpu', 'ram', 'disk'], true)) {
        $tab = 'info';
    }
    return view('processes.show', compact('name', 'tab'));
})
    ->where('name', '[A-Za-z0-9._-]+')
    ->middleware(['auth', 'verified'])
    ->name('processes.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Polling endpoints (JSON) — apelate de poller.js la interval = bucketSeconds.
Route::middleware(['auth'])->prefix('poll')->group(function () {
    Route::get('/metrics',          [PollMetricsController::class,        'snapshot'])->name('poll.metrics');
    Route::get('/apache-logs',      [PollApacheLogsController::class,     'snapshot'])->name('poll.apache-logs');
    Route::get('/process-metrics',  [PollProcessMetricsController::class, 'snapshot'])->name('poll.process-metrics');
    Route::get('/connection',       [PollConnectionsController::class,    'snapshot'])->name('poll.connection');
});

require __DIR__ . '/auth.php';

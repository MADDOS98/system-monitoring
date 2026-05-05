<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ApacheLog;

class ApacheLogsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public function render()
    {
        $logs = ApacheLog::orderByDesc('log_time')->paginate(20);

        return view('livewire.apache-logs-table', [
            'logs' => $logs
        ]);
    }
}
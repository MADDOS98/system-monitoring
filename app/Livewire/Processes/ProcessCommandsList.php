<?php

namespace App\Livewire\Processes;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProcessCommandsList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $name;

    private const PER_PAGE = 10;

    public function mount(string $name): void
    {
        $this->name = $name;
    }

    public function render()
    {
        $commands = DB::connection('process_metrics')
            ->table('process_commands AS pc')
            ->join('process_names AS pn', 'pn.id', '=', 'pc.process_name_id')
            ->where('pn.name', $this->name)
            ->orderBy('pc.id')
            ->select(['pc.id', 'pc.command'])
            ->paginate(self::PER_PAGE);

        return view('livewire.processes.process-commands-list', [
            'commands' => $commands,
        ]);
    }
}

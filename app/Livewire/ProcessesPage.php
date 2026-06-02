<?php

namespace App\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProcessesPage extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search  = '';
    public string $sortBy  = 'cpu_pct';
    public string $sortDir = 'desc';

    private const PER_PAGE = 15;

    // Whitelist coloane sortabile + alias SQL pentru ORDER BY.
    private const SORTABLE = [
        'name'              => 'pn.name',
        'count'             => 'pm.count',
        'cpu_pct'           => 'pm.cpu_pct',
        'ram_kb'            => 'pm.ram_kb',
        'read_bytes'        => 'pm.read_bytes',
        'write_bytes'       => 'pm.write_bytes',
        'last_collected_at' => 'pm.collected_at',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if (! array_key_exists($column, self::SORTABLE)) {
            return;
        }
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'desc';
        }
        $this->resetPage();
    }

    public function render()
    {
        $sortCol = self::SORTABLE[$this->sortBy] ?? self::SORTABLE['cpu_pct'];
        $sortDir = $this->sortDir === 'asc' ? 'asc' : 'desc';

        $db = DB::connection('process_metrics');

        // Ultimul collected_at per process — folosit ca anchor pentru JOIN-ul cu randul-snapshot.
        $latest = $db->table('process_metrics')
            ->select('process_name_id', DB::raw('MAX(collected_at) AS max_at'))
            ->groupBy('process_name_id');

        $base = $db->table('process_names AS pn')
            ->leftJoinSub($latest, 'latest', 'latest.process_name_id', '=', 'pn.id')
            ->leftJoin('process_metrics AS pm', function ($j) {
                $j->on('pm.process_name_id', '=', 'pn.id')
                  ->on('pm.collected_at', '=', 'latest.max_at');
            });

        if ($this->search !== '') {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $this->search);
            $base->where('pn.name', 'like', '%' . $escaped . '%');
        }

        $total = (clone $base)->count();

        $page    = max(1, (int) $this->getPage());
        $perPage = self::PER_PAGE;

        $items = $base
            ->select([
                'pn.id',
                'pn.name',
                'pm.collected_at AS last_collected_at',
                'pm.count',
                'pm.cpu_pct',
                'pm.ram_kb',
                'pm.read_bytes',
                'pm.write_bytes',
            ])
            ->orderByRaw("$sortCol IS NULL ASC")
            ->orderBy($sortCol, $sortDir)
            ->orderBy('pn.name', 'asc')
            ->forPage($page, $perPage)
            ->get();

        $processes = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return view('livewire.processes-page', [
            'processes' => $processes,
            'total'     => $total,
        ]);
    }
}

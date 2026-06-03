@php
    use Illuminate\Support\Facades\DB;

    // Verifica daca procesul exista — daca nu, vom afisa un mesaj prietenos.
    $exists = DB::connection('process_metrics')
        ->table('process_names')
        ->where('name', $name)
        ->exists();

    // Comenzi distincte (afisate ca subtitlu).
    $commands = $exists
        ? DB::connection('process_metrics')
            ->table('process_commands AS pc')
            ->join('process_names AS pn', 'pn.id', '=', 'pc.process_name_id')
            ->where('pn.name', $name)
            ->orderBy('pc.command')
            ->pluck('pc.command')
            ->all()
        : [];

    $tabs = [
        ['id' => 'cpu', 'label' => 'CPU', 'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>'],
        ['id' => 'ram', 'label' => 'RAM', 'icon' => '<rect x="2" y="8" width="20" height="8" rx="2"/><path d="M6 8V6M10 8V6M14 8V6M18 8V6M6 16v2M10 16v2M14 16v2M18 16v2"/>'],
        ['id' => 'io',  'label' => 'Disk I/O', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>'],
    ];
@endphp

<x-app-layout>

    {{-- Breadcrumb: link inapoi la lista de procese --}}
    <div class="mb-3">
        <a href="{{ route('processes') }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-xs font-mono text-muted hover:text-text transition-colors">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Processes
        </a>
    </div>

    <livewire:time-range-picker :title="'Process: ' . $name" />

    @if(! empty($commands))
        <div class="mb-5 -mt-3 text-xs text-muted font-mono">
            @foreach($commands as $cmd)
                <div class="truncate">{{ $cmd }}</div>
            @endforeach
        </div>
    @endif

    @if(! $exists)
        <div class="rounded-lg border border-border bg-panel p-8 text-center">
            <p class="text-sm font-mono text-muted">
                Process <span class="text-text">"{{ $name }}"</span> not found in process_metrics database.
            </p>
        </div>
    @else
        <div>

            {{-- Tabs --}}
            <div class="border-b border-border mb-6">
                <nav class="-mb-px flex gap-1">
                    @foreach($tabs as $t)
                        <a href="{{ route('processes.show', ['name' => $name, 'tab' => $t['id']]) }}"
                           wire:navigate
                           class="flex items-center gap-2 px-4 py-3 text-sm font-mono transition-colors duration-150 whitespace-nowrap
                                {{ $tab === $t['id']
                                    ? 'border-b-2 border-[#129247] text-text'
                                    : 'border-b-2 border-transparent text-muted hover:text-label hover:border-border' }}">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                {!! $t['icon'] !!}
                            </svg>
                            {{ $t['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>

            {{-- Tab content: o singura componenta cu metric variabil. Key per tab forteaza
                 re-mount cand userul schimba tab-ul (poller, chart si state se reseteaza). --}}
            <livewire:process-chart
                :name="$name"
                :metric="$tab"
                :key="'process-chart-' . $name . '-' . $tab" />

        </div>
    @endif

</x-app-layout>

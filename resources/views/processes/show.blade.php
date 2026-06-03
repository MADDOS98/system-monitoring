@php
    use Illuminate\Support\Facades\DB;

    // Verifica daca procesul exista — daca nu, afisam un mesaj prietenos.
    $exists = DB::connection('process_metrics')
        ->table('process_names')
        ->where('name', $name)
        ->exists();

    // Numar total de comenzi distincte (pentru indicatorul "+N more" din header).
    $commandsTotal = $exists
        ? DB::connection('process_metrics')
            ->table('process_commands AS pc')
            ->join('process_names AS pn', 'pn.id', '=', 'pc.process_name_id')
            ->where('pn.name', $name)
            ->count()
        : 0;

    // In header afisam doar primele 2; restul sunt accesibile prin tab-ul Info.
    $previewCommands = $exists
        ? DB::connection('process_metrics')
            ->table('process_commands AS pc')
            ->join('process_names AS pn', 'pn.id', '=', 'pc.process_name_id')
            ->where('pn.name', $name)
            ->orderBy('pc.id')
            ->limit(2)
            ->pluck('pc.command')
            ->all()
        : [];

    $hiddenCount = max(0, $commandsTotal - count($previewCommands));

    // Info e primul si default. Restul (CPU/RAM/Disk) sunt charts pe metrice
    // de timp; Info combina graficul de count + lista de comenzi.
    $tabs = [
        ['id' => 'info', 'label' => 'Info', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>'],
        ['id' => 'cpu',  'label' => 'CPU',  'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>'],
        ['id' => 'ram',  'label' => 'RAM',  'icon' => '<rect x="2" y="8" width="20" height="8" rx="2"/><path d="M6 8V6M10 8V6M14 8V6M18 8V6M6 16v2M10 16v2M14 16v2M18 16v2"/>'],
        ['id' => 'disk', 'label' => 'Disk', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>'],
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

    {{-- Preview comenzi (max 2) + link la tab-ul Info daca sunt mai multe --}}
    @if(! empty($previewCommands))
        <div class="mb-5 -mt-3 text-xs text-muted font-mono space-y-0.5">
            @foreach($previewCommands as $cmd)
                <div class="truncate">{{ $cmd }}</div>
            @endforeach
            @if($hiddenCount > 0)
                <a href="{{ route('processes.show', ['name' => $name, 'tab' => 'info']) }}"
                   wire:navigate
                   class="inline-flex items-center gap-1 text-[#6b7280] hover:text-text transition-colors mt-0.5">
                    + {{ $hiddenCount }} more
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </a>
            @endif
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

            {{-- Tab content --}}
            @if($tab === 'info')
                {{-- Info: count chart sus + commands list jos --}}
                <livewire:process-chart
                    :name="$name"
                    metric="info"
                    :key="'process-chart-' . $name . '-info'" />

                <div class="mt-6">
                    <livewire:process-commands-list
                        :name="$name"
                        :key="'process-commands-' . $name" />
                </div>
            @else
                {{-- Chart unic pentru cpu/ram/disk --}}
                <livewire:process-chart
                    :name="$name"
                    :metric="$tab"
                    :key="'process-chart-' . $name . '-' . $tab" />
            @endif

        </div>
    @endif

</x-app-layout>

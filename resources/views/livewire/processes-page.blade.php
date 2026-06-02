@php
    use Carbon\Carbon;

    $fmtKb = function (?int $kb): string {
        if ($kb === null) return '—';
        if ($kb >= 1048576) return number_format($kb / 1048576, 2) . ' GB';
        if ($kb >= 1024)    return number_format($kb / 1024, 0)    . ' MB';
        return number_format($kb, 0) . ' KB';
    };
    $fmtBps = function (?int $b): string {
        if ($b === null) return '—';
        if ($b >= 1073741824) return number_format($b / 1073741824, 1) . ' GB/s';
        if ($b >= 1048576)    return number_format($b / 1048576, 1)    . ' MB/s';
        if ($b >= 1024)       return number_format($b / 1024, 1)       . ' KB/s';
        return $b . ' B/s';
    };
    $fmtPct = function (?float $p): string {
        if ($p === null) return '—';
        // cpu_pct e cumulativ pe fereastra de 15s — poate depasi 100% (max ~1500%).
        return number_format($p, 1) . '%';
    };
    $fmtAgo = function (?int $ts): string {
        if ($ts === null) return 'never';
        return Carbon::createFromTimestamp($ts)->diffForHumans(['short' => true]);
    };
    $sortIcon = function (string $col) {
        $active = $this->sortBy === $col;
        $dir    = $this->sortDir;
        if (! $active) {
            return '<svg class="w-3 h-3 text-[#3f3f46]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 10l5-5 5 5M7 14l5 5 5-5"/></svg>';
        }
        if ($dir === 'desc') {
            return '<svg class="w-3 h-3 text-text" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5"/></svg>';
        }
        return '<svg class="w-3 h-3 text-text" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M7 14l5-5 5 5"/></svg>';
    };

    $isFirst = $processes->onFirstPage();
    $isLast  = ! $processes->hasMorePages();
    $disabledClasses = 'text-[#6b7280] opacity-30 cursor-not-allowed pointer-events-none';
    $enabledClasses  = 'text-label hover:text-text';
@endphp

<div class="space-y-3">

    {{-- Page-level header (titlu + subtitlu); search-ul propriu-zis e in toolbar-ul tabelei. --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Processes</h1>
            <p class="text-sm text-muted mt-0.5 font-mono">
                Snapshot of latest collected metric per process &middot; collected every 15s
            </p>
        </div>
    </div>

    {{-- Table card --}}
    <div class="rounded-lg border border-border overflow-hidden flex flex-col">

        {{-- Toolbar: title chip + total + search input ──────────────────── --}}
        <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border">

            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <rect x="2" y="6" width="20" height="12" rx="2"/>
                    <path d="M6 12h4M14 12h4"/>
                </svg>
                <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Processes</span>
                <span class="text-xs font-mono text-[#6b7280]">
                    {{ $total }} total
                </span>
            </div>

            {{-- Search input in toolbar --}}
            <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5 w-64">
                <svg class="w-3.5 h-3.5 text-[#6b7280] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                </svg>
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="search process name..."
                       class="bg-transparent text-xs text-[#e5e7eb] font-mono border-none outline-none focus:ring-0 p-0 w-full placeholder-[#6b7280]" />
            </div>

        </div>

        {{-- Column headers (sortable) ────────────────────────────────────── --}}
        <div class="grid grid-cols-12 px-4 py-2.5 bg-sidebar border-b border-border flex-shrink-0 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">
            <button type="button" wire:click="sort('name')"
                    class="col-span-3 flex items-center gap-1.5 text-left hover:text-text transition-colors cursor-pointer">
                Process {!! $sortIcon('name') !!}
            </button>
            <button type="button" wire:click="sort('count')"
                    class="col-span-1 flex items-center gap-1.5 hover:text-text transition-colors cursor-pointer">
                Count {!! $sortIcon('count') !!}
            </button>
            <button type="button" wire:click="sort('cpu_pct')"
                    class="col-span-2 flex items-center gap-1.5 hover:text-text transition-colors cursor-pointer">
                CPU {!! $sortIcon('cpu_pct') !!}
            </button>
            <button type="button" wire:click="sort('ram_kb')"
                    class="col-span-2 flex items-center gap-1.5 hover:text-text transition-colors cursor-pointer">
                RAM {!! $sortIcon('ram_kb') !!}
            </button>
            <button type="button" wire:click="sort('read_bytes')"
                    class="col-span-1 flex items-center gap-1.5 hover:text-text transition-colors cursor-pointer">
                Read/s {!! $sortIcon('read_bytes') !!}
            </button>
            <button type="button" wire:click="sort('write_bytes')"
                    class="col-span-1 flex items-center gap-1.5 hover:text-text transition-colors cursor-pointer">
                Write/s {!! $sortIcon('write_bytes') !!}
            </button>
            <button type="button" wire:click="sort('last_collected_at')"
                    class="col-span-2 flex items-center gap-1.5 justify-end hover:text-text transition-colors cursor-pointer">
                Last sample {!! $sortIcon('last_collected_at') !!}
            </button>
        </div>

        {{-- Rows ─────────────────────────────────────────────────────────── --}}
        {{-- Inaltime stabila pentru 15 randuri (= per_page) ca pagina sa nu salte la ultima pagina. --}}
        <div class="divide-y divide-[#2a2a2a] overflow-y-auto" style="height: calc(15 * 50.7px)">
            @forelse ($processes as $p)
                <div wire:key="proc-{{ $p->id }}"
                     class="grid grid-cols-12 px-4 py-3 items-center bg-[#111111] hover:bg-[#161616] transition-[background-color] duration-100">

                    {{-- Process name (link placeholder pentru pagina de detaliu, viitor) --}}
                    <div class="col-span-3 min-w-0">
                        <span class="text-sm font-mono text-text truncate block">{{ $p->name }}</span>
                    </div>

                    {{-- Count (× N badge) --}}
                    <div class="col-span-1">
                        @if($p->count !== null)
                            <span class="inline-flex items-center text-[11px] font-mono text-[#9ca3af] bg-[#1f2937] border border-[#2a2a2a] rounded px-1.5 py-0.5">
                                &times; {{ number_format($p->count) }}
                            </span>
                        @else
                            <span class="text-[11px] font-mono text-[#6b7280]">—</span>
                        @endif
                    </div>

                    {{-- CPU --}}
                    <div class="col-span-2 text-sm font-mono text-text">
                        {{ $fmtPct($p->cpu_pct !== null ? (float) $p->cpu_pct : null) }}
                    </div>

                    {{-- RAM --}}
                    <div class="col-span-2 text-sm font-mono text-text">
                        {{ $fmtKb($p->ram_kb !== null ? (int) $p->ram_kb : null) }}
                    </div>

                    {{-- Read/s --}}
                    <div class="col-span-1 text-xs font-mono text-[#9ca3af]">
                        {{ $fmtBps($p->read_bytes !== null ? (int) $p->read_bytes : null) }}
                    </div>

                    {{-- Write/s --}}
                    <div class="col-span-1 text-xs font-mono text-[#9ca3af]">
                        {{ $fmtBps($p->write_bytes !== null ? (int) $p->write_bytes : null) }}
                    </div>

                    {{-- Last sample --}}
                    <div class="col-span-2 text-xs font-mono text-[#6b7280] text-right">
                        {{ $fmtAgo($p->last_collected_at !== null ? (int) $p->last_collected_at : null) }}
                    </div>

                </div>
            @empty
                <div class="flex items-center justify-center py-12 text-[#6b7280] text-sm font-mono">
                    @if($search !== '')
                        No processes match "{{ $search }}".
                    @else
                        No processes recorded yet.
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Pagination footer — exact ca apache-logs-table ─────────────── --}}
        <div data-pagination class="px-4 py-3 border-t border-border bg-sidebar flex items-center justify-between flex-shrink-0">

            <button
                data-prev-button
                wire:click="previousPage"
                wire:loading.attr="disabled"
                @if($isFirst) disabled @endif
                class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isFirst ? $disabledClasses : $enabledClasses }}">
                &larr; Previous
            </button>

            <span data-pagination-summary class="text-xs font-mono text-[#6b7280]">
                {{ $processes->firstItem() ?? 0 }}&ndash;{{ $processes->lastItem() ?? 0 }} of {{ $processes->total() }}
            </span>

            <button
                data-next-button
                wire:click="nextPage"
                wire:loading.attr="disabled"
                @if($isLast) disabled @endif
                class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isLast ? $disabledClasses : $enabledClasses }}">
                Next &rarr;
            </button>

        </div>

    </div>

</div>

@php
    $isFirst = $commands->onFirstPage();
    $isLast  = ! $commands->hasMorePages();
    $disabledClasses = 'text-[#6b7280] opacity-30 cursor-not-allowed pointer-events-none';
    $enabledClasses  = 'text-label hover:text-text';
@endphp

<div wire:key="process-commands-list" class="rounded-lg border border-border overflow-hidden flex flex-col">

    {{-- Toolbar: chip + total count ────────────────────────────────────── --}}
    <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border">
        <div class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
            </svg>
            <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Commands</span>
            <span class="text-xs font-mono text-[#6b7280]">
                {{ $commands->total() }} distinct
            </span>
        </div>
    </div>

    {{-- Rows ────────────────────────────────────────────────────────────── --}}
    <div class="divide-y divide-[#2a2a2a]">
        @forelse ($commands as $cmd)
            <div wire:key="cmd-{{ $cmd->id }}"
                 class="flex items-center gap-3 px-4 py-3 bg-[#111111] hover:bg-[#161616] transition-[background-color] duration-100">
                <span class="text-[10px] font-mono text-[#6b7280] w-6 flex-shrink-0 text-right">
                    #{{ ($commands->firstItem() ?? 0) + $loop->index }}
                </span>
                <code class="text-sm font-mono text-text break-all">{{ $cmd->command }}</code>
            </div>
        @empty
            <div class="flex items-center justify-center py-12 text-[#6b7280] text-sm font-mono">
                No commands recorded for this process.
            </div>
        @endforelse
    </div>

    {{-- Pagination footer ────────────────────────────────────────────── --}}
    @if($commands->total() > 0)
        <div data-pagination class="px-4 py-3 border-t border-border bg-sidebar flex items-center justify-between flex-shrink-0">

            <button
                wire:click="previousPage"
                wire:loading.attr="disabled"
                @if($isFirst) disabled @endif
                class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isFirst ? $disabledClasses : $enabledClasses }}">
                &larr; Previous
            </button>

            <span class="text-xs font-mono text-[#6b7280]">
                {{ $commands->firstItem() ?? 0 }}&ndash;{{ $commands->lastItem() ?? 0 }} of {{ $commands->total() }}
            </span>

            <button
                wire:click="nextPage"
                wire:loading.attr="disabled"
                @if($isLast) disabled @endif
                class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isLast ? $disabledClasses : $enabledClasses }}">
                Next &rarr;
            </button>

        </div>
    @endif

</div>

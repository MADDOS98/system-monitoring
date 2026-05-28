<div wire:poll.5s class="w-full rounded-lg border border-border bg-panel overflow-hidden flex flex-col"
    style="min-height: calc(100vh - 160px)">

    @php
    $levelStyles = [
    'critical' => [
    'dot' => 'bg-red-500',
    'pulse' => 'animate-pulse',
    'badge' => 'bg-red-950 text-red-400 border border-red-900',
    'badgeMuted' => 'bg-red-950/50 text-red-500 border border-red-900/50',
    'value' => 'text-red-400',
    'snooze' => 'bg-red-950/40 border-red-900/60 text-red-300 hover:bg-red-950/60',
    'label' => 'CRITICAL',
    ],
    'warning' => [
    'dot' => 'bg-amber-500',
    'pulse' => '',
    'badge' => 'bg-amber-950 text-amber-400 border border-amber-900',
    'badgeMuted' => 'bg-amber-950/50 text-amber-500 border border-amber-900/50',
    'value' => 'text-amber-400',
    'snooze' => 'bg-amber-950/40 border-amber-900/60 text-amber-300 hover:bg-amber-950/60',
    'label' => 'WARNING',
    ],
    'info' => [
    'dot' => 'bg-blue-500',
    'pulse' => '',
    'badge' => 'bg-blue-950 text-blue-400 border border-blue-900',
    'badgeMuted' => 'bg-blue-950/50 text-blue-500 border border-blue-900/50',
    'value' => 'text-blue-400',
    'snooze' => 'bg-blue-950/40 border-blue-900/60 text-blue-300 hover:bg-blue-950/60',
    'label' => 'INFO',
    ],
    ];

    $metricToTab = [
    'cpu' => 'cpu', 'cpu_stolen' => 'cpu', 'ram' => 'ram',
    'disk_io_read' => 'disk', 'disk_io_write' => 'disk',
    'network_in' => 'network', 'network_out' => 'network',
    ];

    $metricUnits = [
    'cpu' => '%', 'cpu_stolen' => '%', 'ram' => '%',
    'disk_io_read' => 'MB/s', 'disk_io_write' => 'MB/s',
    'network_in' => 'Mbps', 'network_out' => 'Mbps',
    ];

    $metricLabels = [
    'cpu' => 'cpu.total_usage',
    'cpu_stolen' => 'cpu.stolen_usage',
    'ram' => 'ram.used_pct',
    'disk_io_read' => 'disk.read_bps',
    'disk_io_write' => 'disk.write_bps',
    'network_in' => 'net.rx_bps',
    'network_out' => 'net.tx_bps',
    ];
    @endphp

    {{-- Panel header: tabs --}}
    <div class="flex items-center gap-5 px-4 py-3 bg-sidebar border-b border-border">
        <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
            <button type="button" wire:click="setTab('active')"
                class="flex items-center gap-2 px-3 py-1.5 text-xs font-mono transition-colors duration-150
                           {{ $tab === 'active' ? 'bg-[#1f2937] text-text' : 'text-muted hover:text-text' }}">
                <span class="w-1.5 h-1.5 rounded-full bg-red-500 {{ $activeCount > 0 ? 'animate-pulse' : '' }}"></span>
                Active
                <span class="text-[10px] bg-red-700 text-white rounded-full px-1.5 py-0.5 min-w-[18px] text-center">{{ $activeCount }}</span>
            </button>
            <button type="button" wire:click="setTab('read')"
                class="flex items-center gap-2 px-3 py-1.5 text-xs font-mono transition-colors duration-150 border-l border-border
                           {{ $tab === 'read' ? 'bg-[#1f2937] text-text' : 'text-muted hover:text-text' }}">
                Read
                <span class="text-[10px] bg-neutral-700 text-neutral-300 rounded-full px-1.5 py-0.5 min-w-[18px] text-center">{{ $readCount }}</span>
            </button>
        </div>

        {{-- Filter bar: search + level filters + metric filter + counter --}}
        <div class="flex items-center justify-between gap-3 px-3">
            {{-- Search --}}
            <div class="relative flex-2 max-w-xs">
                <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted pointer-events-none"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7" />
                    <path d="M21 21l-4.3-4.3" />
                </svg>
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search alerts..."
                    class="w-full pl-8 pr-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono placeholder:text-muted outline-none focus:border-neutral-600">
            </div>

            {{-- Level filters --}}
            @php
            $levelFilters = [
            'all' => ['label' => 'All', 'count' => $tabTotal, 'active' => 'text-text'],
            'critical' => ['label' => 'Critical', 'count' => $levelCounts['critical'] ?? 0, 'active' => 'text-red-400'],
            'warning' => ['label' => 'Warning', 'count' => $levelCounts['warning'] ?? 0, 'active' => 'text-amber-400'],
            'info' => ['label' => 'Info', 'count' => $levelCounts['info'] ?? 0, 'active' => 'text-blue-400'],
            ];
            @endphp
            <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
                @foreach($levelFilters as $key => $f)
                <button type="button" wire:click="setLevelFilter('{{ $key }}')"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-mono transition-colors duration-150 {{ !$loop->first ? 'border-l border-border' : '' }}
                               {{ $levelFilter === $key ? 'bg-[#1f2937] ' . $f['active'] : 'text-muted hover:text-text' }}">
                    {{ $f['label'] }}
                    <span class="text-[10px] {{ $levelFilter === $key ? 'text-neutral-400' : 'text-neutral-600' }}">{{ $f['count'] }}</span>
                </button>
                @endforeach
            </div>

            {{-- Metric filter --}}
            <select wire:model.live="metricFilter"
                class="px-2.5 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600 cursor-pointer">
                <option value="all">All metrics</option>
                @foreach($allMetrics as $m)
                <option value="{{ $m }}">{{ $m }}</option>
                @endforeach
            </select>

            {{-- Counter --}}
            <span class="text-[11px] font-mono text-muted whitespace-nowrap">
                {{ $alerts->count() }} {{ $alerts->count() === 1 ? 'alert' : 'alerts' }}
            </span>
        </div>

        {{-- Action buttons --}}
        <div class="flex items-center gap-2 ml-auto">

            {{-- Read all (cu confirmare double-click). Doar pe tab active si daca exista alerte vizibile. --}}
            @if($tab === 'active' && $alerts->count() > 0)
                @if($confirmingReadAll)
                    <button type="button" wire:click="confirmReadAll"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-mono
                                   bg-amber-500/15 border border-amber-500/40 text-amber-300 hover:bg-amber-500/25 transition-colors duration-150">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Confirm? Read {{ $alerts->count() }} alert{{ $alerts->count() === 1 ? '' : 's' }}
                    </button>
                    <button type="button" wire:click="cancelReadAll"
                            class="px-2.5 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-neutral-500 hover:text-neutral-200 transition-colors duration-150">
                        Cancel
                    </button>
                @else
                    <button type="button" wire:click="requestReadAll"
                            class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M20 6L9 17l-5-5"/>
                        </svg>
                        Read all
                    </button>
                @endif
            @endif

            <button type="button"
                onclick="Livewire.dispatch('open-alert-rules-manager')"
                class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="3" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
                Manage Alerts
            </button>
        </div>
    </div>



    {{-- Alert list --}}
    <div class="flex-1 overflow-y-auto divide-y divide-border">
        @forelse($alerts as $alert)
        @php
        $style = $levelStyles[$alert->level] ?? $levelStyles['info'];
        $isRead = $alert->isRead();
        $unit = $metricUnits[$alert->metric] ?? '';
        $metricTab = $metricToTab[$alert->metric] ?? 'cpu';
        $sourceLabel = $metricLabels[$alert->metric] ?? $alert->metric;
        $ruleName = $alert->rule?->name ?? ucfirst($alert->level) . ' ' . $alert->metric;
        $triggeredAt = \Carbon\Carbon::createFromTimestamp($alert->window_end);
        $ago = $triggeredAt->diffForHumans(['short' => true]);
        @endphp

        <div wire:key="alert-{{ $alert->id }}"
            x-data="{ open: false }"
            class="bg-panel {{ $isRead ? 'opacity-70' : '' }}">
            <div @click="open = !open"
                class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $isRead ? 'bg-neutral-600' : $style['dot'] . ' ' . $style['pulse'] }}"></span>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded {{ $isRead ? $style['badgeMuted'] : $style['badge'] }}">{{ $style['label'] }}</span>
                    <span class="text-sm font-mono font-semibold {{ $isRead ? 'text-label' : 'text-text' }}">{{ $ruleName }}</span>
                    <span class="text-xs font-mono text-muted truncate">{{ $alert->message }}</span>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    @unless($isRead)
                    <button @click.stop type="button"
                        wire:click="markAsRead({{ $alert->id }})"
                        title="Mark as read"
                        class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path d="M20 6L9 17l-5-5" />
                        </svg>
                        Mark as read
                    </button>
                    @endunless
                    <a @click.stop href="{{ route('metrics', ['tab' => $metricTab]) }}"
                        wire:navigate
                        title="View metric"
                        class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M7 17l9.2-9.2M17 17V7H7" />
                        </svg>
                        View metric
                    </a>
                    <span class="text-[11px] font-mono text-muted ml-1">{{ $ago }}</span>
                    <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                        :class="open ? 'rotate-180' : ''"
                        fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
            <div x-show="open" x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                    <div class="flex justify-between">
                        <span class="text-muted">Triggered</span>
                        <span class="text-text">{{ $triggeredAt->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Source</span>
                        <span class="text-text">{{ $sourceLabel }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Peak observed</span>
                        <span class="{{ $style['value'] }}">{{ number_format($alert->peak_value, 2) }} {{ $unit }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Threshold</span>
                        <span class="text-text">{{ $alert->operator }} {{ $alert->threshold }} {{ $unit }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Window</span>
                        <span class="text-text">{{ $alert->window_end - $alert->window_start }} s sustained</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Samples</span>
                        <span class="text-text">{{ $alert->matched_count }} / {{ $alert->sample_count }} ({{ round($alert->ratio_observed * 100) }}%)</span>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="flex-1 flex items-center justify-center py-16">
            <div class="text-center">
                @if($this->hasActiveFilters())
                <p class="text-sm font-mono text-muted">No alerts match the current filters.</p>
                <button type="button" wire:click="clearFilters"
                    class="mt-2 px-3 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-label hover:text-text transition-colors duration-150">
                    Clear filters
                </button>
                @else
                <p class="text-sm font-mono text-muted">
                    {{ $tab === 'active' ? 'No active alerts.' : 'No read alerts.' }}
                </p>
                <p class="text-[11px] font-mono text-neutral-600 mt-1">
                    {{ $tab === 'active' ? 'All systems operational.' : 'Mark active alerts as read to see them here.' }}
                </p>
                @endif
            </div>
        </div>
        @endforelse
    </div>
</div>
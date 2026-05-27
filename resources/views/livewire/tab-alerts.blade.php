<div wire:poll.30s class="mb-6">
    @php
        $levelStyles = [
            'critical' => [
                'dot'   => 'bg-red-500 animate-pulse',
                'badge' => 'bg-red-950 text-red-400 border border-red-900',
                'label' => 'CRITICAL',
            ],
            'warning' => [
                'dot'   => 'bg-amber-500',
                'badge' => 'bg-amber-950 text-amber-400 border border-amber-900',
                'label' => 'WARNING',
            ],
            'info' => [
                'dot'   => 'bg-blue-500',
                'badge' => 'bg-blue-950 text-blue-400 border border-blue-900',
                'label' => 'INFO',
            ],
        ];
    @endphp

    @if($alerts->isEmpty())
        <div class="flex items-center gap-2 px-4 py-2 rounded-md border border-border bg-panel/50 text-[11px] font-mono text-muted">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            No active alerts for <span class="text-text">{{ strtoupper($tab) }}</span>.
        </div>
    @else
        <div class="rounded-md border border-border bg-panel overflow-hidden">
            <div class="flex items-center justify-between px-4 py-2 bg-sidebar border-b border-border">
                <p class="text-[11px] font-mono uppercase tracking-widest text-muted">
                    Active alerts
                    <span class="text-[10px] bg-red-700 text-white rounded-full px-1.5 py-0.5 ml-1">{{ $alerts->count() }}</span>
                </p>
                <div class="flex items-center gap-3">
                    {{-- Read all (cu confirmare double-click) --}}
                    @if($confirmingReadAll)
                        <button type="button" wire:click="confirmReadAll"
                                class="flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono
                                       bg-amber-500/15 border border-amber-500/40 text-amber-300 hover:bg-amber-500/25 transition-colors duration-150">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            Confirm? Read {{ $alerts->count() }}
                        </button>
                        <button type="button" wire:click="cancelReadAll"
                                class="text-[11px] font-mono text-neutral-500 hover:text-neutral-200 transition-colors">
                            Cancel
                        </button>
                    @else
                        <button type="button" wire:click="requestReadAll"
                                class="flex items-center gap-1 text-[11px] font-mono text-muted hover:text-text transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            Read all
                        </button>
                    @endif

                    <a href="{{ route('alerts') }}" wire:navigate
                       class="text-[11px] font-mono text-muted hover:text-text transition-colors">
                        View all &rarr;
                    </a>
                </div>
            </div>
            <div class="divide-y divide-border overflow-y-auto max-h-40">
                @foreach($alerts as $alert)
                    @php
                        $style    = $levelStyles[$alert->level] ?? $levelStyles['info'];
                        $ruleName = $alert->rule?->name ?? (ucfirst($alert->level) . ' ' . $alert->metric);
                        $ago      = \Carbon\Carbon::createFromTimestamp($alert->window_end)->diffForHumans(['short' => true]);
                    @endphp
                    <div wire:key="tab-alert-{{ $alert->id }}"
                         class="flex items-center justify-between gap-3 px-4 py-2 hover:bg-[#161616] transition-colors duration-150">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $style['dot'] }}"></span>
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded {{ $style['badge'] }}">{{ $style['label'] }}</span>
                            <span class="text-xs font-mono font-semibold text-text">{{ $ruleName }}</span>
                            <span class="text-[11px] font-mono text-muted truncate">{{ $alert->message }}</span>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <button type="button"
                                    wire:click="markAsRead({{ $alert->id }})"
                                    title="Mark as read"
                                    class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                                Mark as read
                            </button>
                            <span class="text-[11px] font-mono text-muted">{{ $ago }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

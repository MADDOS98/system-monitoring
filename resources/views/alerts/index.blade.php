<x-app-layout>

    {{-- Page header --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Alerts</h1>
            <p class="text-sm text-muted mt-0.5 font-mono">
                Real-time monitoring alerts ·
                <span class="text-text">5 active</span>
                ·
                <span class="text-muted">12 read</span>
            </p>
        </div>
    </div>

    {{-- Main alerts panel --}}
    <div x-data="{ tab: 'active' }"
         class="w-full rounded-lg border border-border bg-panel overflow-hidden flex flex-col"
         style="min-height: calc(100vh - 160px)">

        {{-- Panel header: tabs + actions --}}
        <div class="flex items-center justify-between px-4 py-3 bg-sidebar border-b border-border">

            {{-- Tabs --}}
            <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
                <button @click="tab = 'active'"
                        :class="tab === 'active' ? 'bg-[#1f2937] text-text' : 'text-muted hover:text-text'"
                        class="flex items-center gap-2 px-3 py-1.5 text-xs font-mono transition-colors duration-150">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                    Active
                    <span class="text-[10px] bg-red-700 text-white rounded-full px-1.5 py-0.5 min-w-[18px] text-center">5</span>
                </button>
                <button @click="tab = 'read'"
                        :class="tab === 'read' ? 'bg-[#1f2937] text-text' : 'text-muted hover:text-text'"
                        class="flex items-center gap-2 px-3 py-1.5 text-xs font-mono transition-colors duration-150 border-l border-border">
                    Read
                    <span class="text-[10px] bg-neutral-700 text-neutral-300 rounded-full px-1.5 py-0.5 min-w-[18px] text-center">12</span>
                </button>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center gap-2">
                <button class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Mark all as read
                </button>
                <button class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                    Clear all
                </button>
                <button class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                    </svg>
                    Manage rules
                </button>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- ACTIVE TAB                                                   --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'active'" class="flex-1 overflow-y-auto divide-y divide-border">

            {{-- ============== Alert: CPU critical (danger) ============== --}}
            <div x-data="{ open: false }" class="bg-panel">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-red-950 text-red-400 border border-red-900">DANGER</span>
                        <span class="text-sm font-mono font-semibold text-text">CPU critical</span>
                        <span class="text-xs font-mono text-muted truncate">CPU usage 92% &ge; 95% for 1 min</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button @click.stop type="button" title="Mark as read"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            Mark as read
                        </button>
                        <button @click.stop type="button" title="View metric"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                            View metric
                        </button>
                        <span class="text-[11px] font-mono text-muted ml-1">2 min ago</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
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
                            <span class="text-text">2026-05-21 14:23:01</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Source</span>
                            <span class="text-text">cpu.total_usage</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Observed value</span>
                            <span class="text-red-400">92.4 %</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Threshold</span>
                            <span class="text-text">&ge; 95 %</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Window</span>
                            <span class="text-text">60 s sustained</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted">Cooldown</span>
                            <span class="text-text">300 s</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-4 pt-3 border-t border-border">
<button class="ml-auto px-3 py-1.5 bg-red-950/40 border border-red-900/60 rounded text-xs font-mono text-red-300 hover:bg-red-950/60 transition-colors duration-150">Snooze 1h</button>
                    </div>
                </div>
            </div>

            {{-- ============== Alert: RAM high (warning) ============== --}}
            <div x-data="{ open: false }" class="bg-panel">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-amber-950 text-amber-400 border border-amber-900">WARNING</span>
                        <span class="text-sm font-mono font-semibold text-text">RAM high</span>
                        <span class="text-xs font-mono text-muted truncate">RAM 87% &ge; 85% for 3 min</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button @click.stop type="button" title="Mark as read"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            Mark as read
                        </button>
                        <button @click.stop type="button" title="View metric"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                            View metric
                        </button>
                        <span class="text-[11px] font-mono text-muted ml-1">5 min ago</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-cloak
                     class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                        <div class="flex justify-between"><span class="text-muted">Triggered</span><span class="text-text">2026-05-21 14:20:14</span></div>
                        <div class="flex justify-between"><span class="text-muted">Source</span><span class="text-text">ram.used_pct</span></div>
                        <div class="flex justify-between"><span class="text-muted">Observed value</span><span class="text-amber-400">87.2 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Threshold</span><span class="text-text">&ge; 85 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Window</span><span class="text-text">180 s sustained</span></div>
                        <div class="flex justify-between"><span class="text-muted">Cooldown</span><span class="text-text">600 s</span></div>
                    </div>
                    <div class="flex items-center gap-2 mt-4 pt-3 border-t border-border">
<button class="ml-auto px-3 py-1.5 bg-amber-950/40 border border-amber-900/60 rounded text-xs font-mono text-amber-300 hover:bg-amber-950/60 transition-colors duration-150">Snooze 1h</button>
                    </div>
                </div>
            </div>

            {{-- ============== Alert: Disk near full (warning) ============== --}}
            <div x-data="{ open: false }" class="bg-panel">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-amber-950 text-amber-400 border border-amber-900">WARNING</span>
                        <span class="text-sm font-mono font-semibold text-text">Disk near full</span>
                        <span class="text-xs font-mono text-muted truncate">Disk 87% &ge; 85%</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button @click.stop type="button" title="Mark as read"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            Mark as read
                        </button>
                        <button @click.stop type="button" title="View metric"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                            View metric
                        </button>
                        <span class="text-[11px] font-mono text-muted ml-1">12 min ago</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-cloak
                     class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                        <div class="flex justify-between"><span class="text-muted">Triggered</span><span class="text-text">2026-05-21 14:13:42</span></div>
                        <div class="flex justify-between"><span class="text-muted">Source</span><span class="text-text">disk.used_pct</span></div>
                        <div class="flex justify-between"><span class="text-muted">Observed value</span><span class="text-amber-400">87.0 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Threshold</span><span class="text-text">&ge; 85 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Window</span><span class="text-text">single sample</span></div>
                        <div class="flex justify-between"><span class="text-muted">Cooldown</span><span class="text-text">1800 s</span></div>
                    </div>
                    <div class="flex items-center gap-2 mt-4 pt-3 border-t border-border">
<button class="ml-auto px-3 py-1.5 bg-amber-950/40 border border-amber-900/60 rounded text-xs font-mono text-amber-300 hover:bg-amber-950/60 transition-colors duration-150">Snooze 1h</button>
                    </div>
                </div>
            </div>

            {{-- ============== Alert: HTTP 5xx surge (danger) ============== --}}
            <div x-data="{ open: false }" class="bg-panel">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-red-950 text-red-400 border border-red-900">DANGER</span>
                        <span class="text-sm font-mono font-semibold text-text">HTTP 5xx surge</span>
                        <span class="text-xs font-mono text-muted truncate">15 req/min &ge; 10 req/min</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button @click.stop type="button" title="Mark as read"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            Mark as read
                        </button>
                        <button @click.stop type="button" title="View"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                            View
                        </button>
                        <span class="text-[11px] font-mono text-muted ml-1">1 h ago</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-cloak
                     class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                        <div class="flex justify-between"><span class="text-muted">Triggered</span><span class="text-text">2026-05-21 13:25:09</span></div>
                        <div class="flex justify-between"><span class="text-muted">Source</span><span class="text-text">apache.5xx_rate_per_min</span></div>
                        <div class="flex justify-between"><span class="text-muted">Observed value</span><span class="text-red-400">15 req/min</span></div>
                        <div class="flex justify-between"><span class="text-muted">Threshold</span><span class="text-text">&ge; 10 req/min</span></div>
                        <div class="flex justify-between"><span class="text-muted">Window</span><span class="text-text">60 s sustained</span></div>
                        <div class="flex justify-between"><span class="text-muted">Cooldown</span><span class="text-text">300 s</span></div>
                    </div>
                    <div class="flex items-center gap-2 mt-4 pt-3 border-t border-border">
<button class="ml-auto px-3 py-1.5 bg-red-950/40 border border-red-900/60 rounded text-xs font-mono text-red-300 hover:bg-red-950/60 transition-colors duration-150">Snooze 1h</button>
                    </div>
                </div>
            </div>

            {{-- ============== Alert: Network RX burst (info) ============== --}}
            <div x-data="{ open: false }" class="bg-panel">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-950 text-blue-400 border border-blue-900">INFO</span>
                        <span class="text-sm font-mono font-semibold text-text">Network RX burst</span>
                        <span class="text-xs font-mono text-muted truncate">12 MB/s &ge; 8 MB/s for 60 s</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <button @click.stop type="button" title="Mark as read"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                            Mark as read
                        </button>
                        <button @click.stop type="button" title="View"
                                class="px-2 py-1 rounded text-[11px] font-mono text-label hover:text-text hover:bg-neutral-800 transition-colors duration-150 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
                            View
                        </button>
                        <span class="text-[11px] font-mono text-muted ml-1">1 h ago</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-cloak
                     class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                        <div class="flex justify-between"><span class="text-muted">Triggered</span><span class="text-text">2026-05-21 13:20:55</span></div>
                        <div class="flex justify-between"><span class="text-muted">Source</span><span class="text-text">net.rx_bps</span></div>
                        <div class="flex justify-between"><span class="text-muted">Observed value</span><span class="text-blue-400">12.1 MB/s</span></div>
                        <div class="flex justify-between"><span class="text-muted">Threshold</span><span class="text-text">&ge; 8.0 MB/s</span></div>
                        <div class="flex justify-between"><span class="text-muted">Window</span><span class="text-text">60 s sustained</span></div>
                        <div class="flex justify-between"><span class="text-muted">Cooldown</span><span class="text-text">600 s</span></div>
                    </div>
                    <div class="flex items-center gap-2 mt-4 pt-3 border-t border-border">
<button class="ml-auto px-3 py-1.5 bg-blue-950/40 border border-blue-900/60 rounded text-xs font-mono text-blue-300 hover:bg-blue-950/60 transition-colors duration-150">Snooze 1h</button>
                    </div>
                </div>
            </div>

        </div>

        {{-- ============================================================ --}}
        {{-- READ TAB                                                     --}}
        {{-- ============================================================ --}}
        <div x-show="tab === 'read'" x-cloak class="flex-1 overflow-y-auto divide-y divide-border">

            {{-- ============== Alert: CPU stolen spike (warning, resolved) ============== --}}
            <div x-data="{ open: false }" class="bg-panel opacity-70">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-neutral-600 flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-amber-950/50 text-amber-500 border border-amber-900/50">WARNING</span>
                        <span class="text-sm font-mono text-label line-through-on-hover">CPU stolen spike</span>
                        <span class="text-xs font-mono text-muted truncate">CPU stolen 12% &ge; 10% — resolved</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-[11px] font-mono text-muted">2 h ago</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-cloak
                     class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                        <div class="flex justify-between"><span class="text-muted">Triggered</span><span class="text-text">2026-05-21 12:23:10</span></div>
                        <div class="flex justify-between"><span class="text-muted">Resolved</span><span class="text-emerald-400">12:25:42 (2 min 32 s)</span></div>
                        <div class="flex justify-between"><span class="text-muted">Source</span><span class="text-text">cpu.stolen_usage</span></div>
                        <div class="flex justify-between"><span class="text-muted">Peak observed</span><span class="text-amber-400">12.4 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Threshold</span><span class="text-text">&ge; 10 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Window</span><span class="text-text">60 s sustained</span></div>
                    </div>
                </div>
            </div>

            {{-- ============== Alert: Disk write storm (info, resolved) ============== --}}
            <div x-data="{ open: false }" class="bg-panel opacity-70">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-neutral-600 flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-blue-950/50 text-blue-500 border border-blue-900/50">INFO</span>
                        <span class="text-sm font-mono text-label">Disk write storm</span>
                        <span class="text-xs font-mono text-muted truncate">230 MB/s &ge; 200 MB/s — resolved</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-[11px] font-mono text-muted">3 h ago</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-cloak
                     class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                        <div class="flex justify-between"><span class="text-muted">Triggered</span><span class="text-text">2026-05-21 11:18:32</span></div>
                        <div class="flex justify-between"><span class="text-muted">Resolved</span><span class="text-emerald-400">11:21:05 (2 min 33 s)</span></div>
                        <div class="flex justify-between"><span class="text-muted">Source</span><span class="text-text">disk.write_bps</span></div>
                        <div class="flex justify-between"><span class="text-muted">Peak observed</span><span class="text-blue-400">232 MB/s</span></div>
                        <div class="flex justify-between"><span class="text-muted">Threshold</span><span class="text-text">&ge; 200 MB/s</span></div>
                        <div class="flex justify-between"><span class="text-muted">Window</span><span class="text-text">60 s sustained</span></div>
                    </div>
                </div>
            </div>

            {{-- ============== Alert: RAM critical (danger, resolved) ============== --}}
            <div x-data="{ open: false }" class="bg-panel opacity-70">
                <div @click="open = !open"
                     class="w-full flex items-center justify-between gap-4 px-4 py-3 hover:bg-[#161616] transition-colors duration-150 cursor-pointer">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="w-2 h-2 rounded-full bg-neutral-600 flex-shrink-0"></span>
                        <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-red-950/50 text-red-500 border border-red-900/50">DANGER</span>
                        <span class="text-sm font-mono text-label">RAM critical</span>
                        <span class="text-xs font-mono text-muted truncate">RAM 95% &ge; 92% — resolved</span>
                    </div>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-[11px] font-mono text-muted">yesterday</span>
                        <svg class="w-3.5 h-3.5 text-muted transition-transform duration-150"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <div x-show="open" x-cloak
                     class="px-4 pb-4 pt-1 bg-[#0d0d0d] border-t border-border">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-mono">
                        <div class="flex justify-between"><span class="text-muted">Triggered</span><span class="text-text">2026-05-20 22:41:08</span></div>
                        <div class="flex justify-between"><span class="text-muted">Resolved</span><span class="text-emerald-400">22:55:14 (14 min 6 s)</span></div>
                        <div class="flex justify-between"><span class="text-muted">Source</span><span class="text-text">ram.used_pct</span></div>
                        <div class="flex justify-between"><span class="text-muted">Peak observed</span><span class="text-red-400">95.8 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Threshold</span><span class="text-text">&ge; 92 %</span></div>
                        <div class="flex justify-between"><span class="text-muted">Window</span><span class="text-text">60 s sustained</span></div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</x-app-layout>

<x-app-layout>

    {{-- Header --}}
    <div
        x-data="{
            preset: '5m',
            from: '',
            to: '',
            init() { this.applyPreset(this.preset); },
            applyPreset(p) {
                this.preset = p;
                const now = new Date();
                const offsets = { '5m': 5, '1h': 60, '24h': 1440 };
                if (offsets[p]) {
                    const past = new Date(now - offsets[p] * 60000);
                    this.from = this.fmt(past);
                    this.to   = this.fmt(now);
                }
            },
            setNow() {
                const now = new Date();
                const offsets = { '5m': 5, '1h': 60, '24h': 1440 };
                const past = new Date(now - (offsets[this.preset] ?? 5) * 60000);
                this.from = this.fmt(past);
                this.to   = this.fmt(now);
            },
            fmt(d) {
                const pad = n => String(n).padStart(2, '0');
                return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate())
                    + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
            },
            fmtDisplay(str) {
                if (!str) return '—';
                return new Date(str).toLocaleString();
            }
        }"
        class="flex items-start justify-between mb-5"
    >
        <div>
            <h1 class="text-xl font-semibold text-text">Apache Traffic</h1>
            <p class="text-xs text-muted font-mono mt-1">
                Window:
                <span class="text-label" x-text="fmtDisplay(from)"></span>
                <span class="mx-1">—</span>
                <span class="text-label" x-text="fmtDisplay(to)"></span>
            </p>
        </div>

        <div class="flex items-center gap-2">
            <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5">
                <span class="text-xs text-muted font-mono">From</span>
                <input type="datetime-local" x-model="from" @change="preset = 'custom'"
                    class="bg-transparent text-xs text-text font-mono border-none outline-none focus:ring-0 p-0" />
            </div>
            <div x-show="preset === 'custom'" x-transition class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5" style="display:none">
                <span class="text-xs text-muted font-mono">To</span>
                <input type="datetime-local" x-model="to"
                    class="bg-transparent text-xs text-text font-mono border-none outline-none focus:ring-0 p-0" />
            </div>
            <button x-show="preset !== 'custom'" @click="setNow()"
                class="px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150">
                Now
            </button>
            <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
                <template x-for="p in ['5m', '1h', '24h', 'custom']" :key="p">
                    <button
                        @click="p !== 'custom' ? applyPreset(p) : (preset = p)"
                        :class="preset === p ? 'bg-[#1f2937] text-[#e5e7eb]' : 'text-[#6b7280] hover:text-[#e5e7eb]'"
                        class="px-3 py-1.5 text-xs font-mono transition-colors duration-150 border-x border-[#2a2a2a]"
                        x-text="p"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="w-full rounded-lg border border-border overflow-hidden"
         x-data="{
             searchField: 'any',
             searchFields: ['any', 'IP', 'URL / endpoint', 'User-Agent', 'HTTP status', 'Method', 'Request ID'],
             searchOpen: false,
             searchQuery: '',
         }"
    >

        {{-- Toolbar --}}
        <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border">

            {{-- Left: Live requests + tailing --}}
            <div class="flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M4 6h16M4 12h16M4 18h7"/>
                </svg>
                <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Live requests</span>
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="text-xs font-mono text-[#6b7280]">tailing</span>
            </div>

            {{-- Right: search field dropdown + search input + All/Slow/Active --}}
            <div class="flex items-center gap-2">

                {{-- Search field dropdown --}}
                <div class="relative" @click.outside="searchOpen = false">
                    <button @click="searchOpen = !searchOpen"
                        class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                        Search: <span x-text="searchField" class="text-[#e5e7eb]"></span>
                        <svg class="w-3 h-3 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="searchOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                        class="absolute left-0 top-full mt-1 w-44 bg-panel border border-border rounded-md shadow-lg z-20 py-1"
                        style="display:none">
                        <template x-for="field in searchFields" :key="field">
                            <button
                                @click="searchField = field; searchOpen = false"
                                :class="searchField === field ? 'bg-blue-600/20 text-blue-400' : 'text-[#9ca3af] hover:text-[#e5e7eb] hover:bg-[#1f1f1f]'"
                                class="w-full text-left px-4 py-2 text-xs font-mono transition-colors duration-100"
                                x-text="field"
                            ></button>
                        </template>
                    </div>
                </div>

                {{-- Search input --}}
                <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5 w-52">
                    <svg class="w-3.5 h-3.5 text-[#6b7280] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input type="text" x-model="searchQuery" placeholder="search live requests..."
                        class="bg-transparent text-xs text-[#e5e7eb] font-mono border-none outline-none focus:ring-0 p-0 w-full placeholder-[#6b7280]" />
                </div>

                {{-- All / Slow / Active --}}
                <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
                    @foreach(['All', 'Slow', 'Active'] as $i => $tab)
                        <button class="px-3 py-1.5 text-xs font-mono transition-colors duration-150
                            {{ $i === 0 ? 'bg-[#1f2937] text-[#e5e7eb]' : 'text-[#6b7280] hover:text-[#e5e7eb]' }}
                            {{ $i > 0 ? 'border-l border-[#2a2a2a]' : '' }}">
                            {{ $tab }}
                        </button>
                    @endforeach
                </div>

            </div>
        </div>

        <div class="overflow-x-auto overflow-y-auto" style="max-height: calc(10 * 41px)">
            <table class="w-full text-xs font-mono">
                <thead>
                    <tr class="bg-sidebar border-b border-border">
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Time</th>
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Method</th>
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Path</th>
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">IP</th>
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">UA</th>
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Bytes</th>
                        <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Referer</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#2a2a2a]">
                    @forelse ($logs as $log)
                        <tr class="bg-[#111111] hover:bg-[#161616] transition-colors duration-100">

                            <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                                {{ isset($log->log_time) ? date('H:i:s', $log->log_time) : '—' }}
                            </td>

                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @php
                                    $mc = match($log->method ?? '') {
                                        'GET'          => 'bg-blue-950 text-blue-400',
                                        'POST'         => 'bg-green-950 text-green-400',
                                        'PUT', 'PATCH' => 'bg-yellow-950 text-yellow-400',
                                        'DELETE'       => 'bg-red-950 text-red-400',
                                        default        => 'bg-zinc-800 text-zinc-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $mc }}">
                                    {{ $log->method ?? '—' }}
                                </span>
                            </td>

                            <td class="px-4 py-2.5 text-[#e5e7eb] max-w-[240px] truncate" title="{{ $log->uri ?? '' }}">
                                {{ $log->uri ?? '—' }}
                            </td>

                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @php
                                    $sc = match(true) {
                                        ($log->status ?? 0) >= 500 => 'bg-red-950 text-red-400',
                                        ($log->status ?? 0) >= 400 => 'bg-yellow-950 text-yellow-400',
                                        ($log->status ?? 0) >= 300 => 'bg-blue-950 text-blue-400',
                                        ($log->status ?? 0) >= 200 => 'bg-green-950 text-green-400',
                                        default                    => 'bg-zinc-800 text-zinc-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $sc }}">
                                    {{ $log->status ?? '—' }}
                                </span>
                            </td>

                            <td class="px-4 py-2.5 text-[#9ca3af] whitespace-nowrap">
                                {{ $log->remote_host ?? '—' }}
                            </td>

                            <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                                @php
                                    $ua = $log->user_agent ?? '';
                                    $uaShort = match(true) {
                                        str_contains($ua, 'curl') || str_contains($ua, 'python') || str_contains($ua, 'Go-http') => 'CLI',
                                        str_contains($ua, 'bot') || str_contains($ua, 'Bot') || str_contains($ua, 'spider')      => 'Bot',
                                        str_contains($ua, 'Chrome')  => 'Chrome',
                                        str_contains($ua, 'Firefox') => 'Firefox',
                                        str_contains($ua, 'Safari')  => 'Safari',
                                        default                      => 'Other',
                                    };
                                @endphp
                                <span title="{{ $ua }}">{{ $uaShort }}</span>
                            </td>

                            <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                                {{ isset($log->bytes_sent) ? number_format($log->bytes_sent) : '—' }}
                            </td>

                            <td class="px-4 py-2.5 text-[#6b7280] max-w-[160px] truncate" title="{{ $log->referer ?? '' }}">
                                {{ ($log->referer ?? '-') === '-' ? '—' : $log->referer }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-[#6b7280]">
                                No log entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="px-4 py-3 border-t border-border bg-sidebar flex items-center justify-between">
                @if ($logs->onFirstPage())
                    <span class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-[#6b7280] opacity-30 cursor-not-allowed">← Newer</span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-label hover:text-text transition-colors duration-150">← Newer</a>
                @endif

                <span class="text-xs font-mono text-[#6b7280]">
                    {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
                </span>

                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-label hover:text-text transition-colors duration-150">Older →</a>
                @else
                    <span class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-[#6b7280] opacity-30 cursor-not-allowed">Older →</span>
                @endif
            </div>
        @endif

    </div>

</x-app-layout>
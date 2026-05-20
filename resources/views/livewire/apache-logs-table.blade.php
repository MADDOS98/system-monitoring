<div wire:key="apache-logs-table"
     data-bucket-seconds="{{ $bucketSeconds }}"
     data-current-page="{{ $logs->currentPage() }}"
     data-has-search="{{ $searchQuery !== '' ? '1' : '0' }}"
     data-search-query="{{ $searchQuery }}"
     data-search-field="{{ $searchField }}"
     data-page-size="20"
     data-from-ts="{{ $this->from }}"
     data-to-ts="{{ $this->to }}">

    {{-- Toolbar cu search --}}
    <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border"
        x-data="{ searchOpen: false }">

        <div class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M4 6h16M4 12h16M4 18h7" />
            </svg>
            <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Live requests</span>
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-xs font-mono text-[#6b7280]">tailing</span>
        </div>

        <div class="flex items-center gap-2">

            {{-- Search field dropdown --}}
            <div class="relative" @click.outside="searchOpen = false">
                <button @click="searchOpen = !searchOpen"
                    class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                    Search: <span class="text-[#e5e7eb]">{{ $searchField }}</span>
                    <svg class="w-3 h-3 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 9l-7 7-7-7" />
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
                    @foreach(['any', 'IP', 'URL / endpoint', 'User-Agent', 'HTTP status', 'Method'] as $field)
                    <button
                        wire:click="$set('searchField', '{{ $field }}')"
                        @click="searchOpen = false"
                        class="w-full text-left px-4 py-2 text-xs font-mono transition-colors duration-100
                                {{ $searchField === $field
                                    ? 'bg-blue-600/20 text-blue-400'
                                    : 'text-[#9ca3af] hover:text-[#e5e7eb] hover:bg-[#1f1f1f]' }}">
                        {{ $field }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Search input --}}
            <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5 w-52">
                <svg class="w-3.5 h-3.5 text-[#6b7280] flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.35-4.35" />
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="search live requests..."
                    class="bg-transparent text-xs text-[#e5e7eb] font-mono border-none outline-none focus:ring-0 p-0 w-full placeholder-[#6b7280]" />
            </div>

        </div>
    </div>

    {{-- Banner "X new entries available" --}}
    <div data-new-entries-banner
         style="display:none"
         class="px-4 py-2 bg-blue-950/40 border-b border-blue-900/60 text-center cursor-pointer hover:bg-blue-950/60 transition-colors duration-150">
        <span class="text-xs font-mono text-blue-300">
            <span data-new-entries-count>0</span> new entries available — click to refresh
        </span>
    </div>

<div class="overflow-x-auto overflow-y-auto" style="max-height: calc(10 * 41px)">

    <table class="w-full text-xs font-mono border-collapse">

        <thead>
            <tr class="bg-sidebar border-b border-border">
                <th class="sticky top-0 z-10 bg-sidebar px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Time</th>
                <th class="sticky top-0 z-10 bg-sidebar px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Method</th>
                <th class="sticky top-0 z-10 bg-sidebar px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Path</th>
                <th class="sticky top-0 z-10 bg-sidebar px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Status</th>
                <th class="sticky top-0 z-10 bg-sidebar px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">MS</th>
                <th class="sticky top-0 z-10 bg-sidebar px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">IP</th>
                <th class="sticky top-0 z-10 bg-sidebar px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">UA</th>
            </tr>
        </thead>

        <tbody data-logs-tbody class="divide-y divide-[#2a2a2a]">
            @forelse ($logs as $log)
            <tr data-log-id="{{ $log->id }}" class="bg-[#111111] hover:bg-[#161616] transition-colors duration-100">

                <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                    {{ isset($log->log_time) ? date('H:i:s', $log->log_time) : '—' }}
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    @php
                    $mc = match($log->method ?? '') {
                        'GET' => 'bg-blue-950 text-blue-400',
                        'POST' => 'bg-green-950 text-green-400',
                        'PUT', 'PATCH' => 'bg-yellow-950 text-yellow-400',
                        'DELETE' => 'bg-red-950 text-red-400',
                        default => 'bg-zinc-800 text-zinc-400',
                    };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $mc }}">
                        {{ $log->method ?? '—' }}
                    </span>
                </td>

                <td class="px-4 py-2.5 text-[#e5e7eb] max-w-[240px] truncate">
                    {{ $log->uri ?? '—' }}
                </td>

                <td class="px-4 py-2.5 whitespace-nowrap">
                    @php
                    $sc = match(true) {
                        ($log->status ?? 0) >= 500 => 'bg-red-950 text-red-400',
                        ($log->status ?? 0) >= 400 => 'bg-yellow-950 text-yellow-400',
                        ($log->status ?? 0) >= 300 => 'bg-blue-950 text-blue-400',
                        ($log->status ?? 0) >= 200 => 'bg-green-950 text-green-400',
                        default => 'bg-zinc-800 text-zinc-400',
                    };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $sc }}">
                        {{ $log->status ?? '—' }}
                    </span>
                </td>

                <td class="px-4 py-2.5 text-[#9ca3af] whitespace-nowrap">—</td>

                <td class="px-4 py-2.5 text-[#9ca3af] whitespace-nowrap">
                    {{ $log->remote_host ?? '—' }}
                </td>

                <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                    @php
                    $ua = $log->user_agent ?? '';
                    $uaShort = match(true) {
                        str_contains($ua, 'curl') || str_contains($ua, 'python') || str_contains($ua, 'Go-http') => 'CLI',
                        str_contains($ua, 'bot') || str_contains($ua, 'Bot') || str_contains($ua, 'spider') => 'Bot',
                        str_contains($ua, 'Chrome') => 'Chrome',
                        str_contains($ua, 'Firefox') => 'Firefox',
                        str_contains($ua, 'Safari') => 'Safari',
                        default => 'Other',
                    };
                    @endphp
                    <span title="{{ $ua }}">{{ $uaShort }}</span>
                </td>
            </tr>
            @empty
            <tr data-empty-row>
                <td colspan="7" class="px-4 py-12 text-center text-[#6b7280]">
                    No log entries found.
                </td>
            </tr>
            @endforelse
        </tbody>

    </table>

</div>

    {{-- Pagination — randata mereu; butoanele sunt dezactivate vizual cand nu sunt necesare. --}}
    @php
        $isFirst   = $logs->onFirstPage();
        $isLast    = ! $logs->hasMorePages();
        $disabledClasses = 'text-[#6b7280] opacity-30 cursor-not-allowed pointer-events-none';
        $enabledClasses  = 'text-label hover:text-text';
    @endphp
    <div data-pagination class="px-4 py-3 border-t border-border bg-sidebar flex items-center justify-between">

        <button
            data-prev-button
            wire:click="previousPage"
            wire:loading.attr="disabled"
            @if($isFirst) disabled @endif
            class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isFirst ? $disabledClasses : $enabledClasses }}">
            ← Newer
        </button>

        <span data-pagination-summary class="text-xs font-mono text-[#6b7280]">
            {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }}
        </span>

        <button
            data-next-button
            wire:click="nextPage"
            wire:loading.attr="disabled"
            @if($isLast) disabled @endif
            class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isLast ? $disabledClasses : $enabledClasses }}">
            Older →
        </button>

    </div>

@script
<script>
(function () {
    const componentId = '{{ $this->getId() }}';
    const PRESET_MINUTES = { '5m': 5, '1h': 60, '24h': 1440 };

    let lastSeenId   = 0;
    let pendingCount = 0;
    let poller       = null;

    function getRoot()  { return document.querySelector('[wire\\:id="' + componentId + '"]'); }
    function getTbody() { return getRoot()?.querySelector('[data-logs-tbody]'); }
    function getBanner(){ return getRoot()?.querySelector('[data-new-entries-banner]'); }

    function getState() {
        const r = getRoot();
        return {
            currentPage: parseInt(r?.dataset.currentPage || '1', 10),
            hasSearch:   r?.dataset.hasSearch === '1',
            searchQuery: r?.dataset.searchQuery || '',
            searchField: r?.dataset.searchField || 'any',
            pageSize:    parseInt(r?.dataset.pageSize || '20', 10),
        };
    }

    function getBucketMs() {
        const sec = parseInt(getRoot()?.dataset.bucketSeconds || '1', 10);
        return Math.max(1, sec) * 1000;
    }

    function getTimeRange() {
        const picker = document.querySelector('[data-live]');
        const live   = picker?.dataset.live === '1';
        const preset = picker?.dataset.preset;
        if (live && PRESET_MINUTES[preset]) {
            const to   = Math.floor(Date.now() / 1000);
            const from = to - PRESET_MINUTES[preset] * 60;
            return { from, to };
        }
        const root = getRoot();
        return {
            from: parseInt(root?.dataset.fromTs || '0', 10),
            to:   parseInt(root?.dataset.toTs   || '0', 10),
        };
    }

    function refreshLastSeenIdFromDom() {
        const tbody = getTbody();
        if (!tbody) return;
        let max = 0;
        tbody.querySelectorAll('[data-log-id]').forEach(tr => {
            const id = parseInt(tr.dataset.logId || '0', 10);
            if (id > max) max = id;
        });
        lastSeenId = max;
    }
    refreshLastSeenIdFromDom();

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function formatTime(ts) {
        const d = new Date(ts * 1000);
        return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    function methodClasses(method) {
        switch (method) {
            case 'GET':            return 'bg-blue-950 text-blue-400';
            case 'POST':           return 'bg-green-950 text-green-400';
            case 'PUT':
            case 'PATCH':          return 'bg-yellow-950 text-yellow-400';
            case 'DELETE':         return 'bg-red-950 text-red-400';
            default:               return 'bg-zinc-800 text-zinc-400';
        }
    }

    function statusClasses(status) {
        const s = parseInt(status, 10) || 0;
        if (s >= 500) return 'bg-red-950 text-red-400';
        if (s >= 400) return 'bg-yellow-950 text-yellow-400';
        if (s >= 300) return 'bg-blue-950 text-blue-400';
        if (s >= 200) return 'bg-green-950 text-green-400';
        return 'bg-zinc-800 text-zinc-400';
    }

    function uaShort(ua) {
        ua = ua || '';
        if (ua.includes('curl') || ua.includes('python') || ua.includes('Go-http')) return 'CLI';
        if (ua.includes('bot')  || ua.includes('Bot')    || ua.includes('spider'))  return 'Bot';
        if (ua.includes('Chrome'))  return 'Chrome';
        if (ua.includes('Firefox')) return 'Firefox';
        if (ua.includes('Safari'))  return 'Safari';
        return 'Other';
    }

    function buildRowHtml(e) {
        const time   = formatTime(e.log_time);
        const method = e.method || '—';
        const uri    = e.uri    || '—';
        const status = e.status ?? '—';
        const ip     = e.remote_host || '—';
        const ua     = e.user_agent || '';
        return `
            <tr data-log-id="${escapeHtml(e.id)}" class="bg-[#111111] hover:bg-[#161616] transition-colors duration-100">
                <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">${escapeHtml(time)}</td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold ${methodClasses(method)}">${escapeHtml(method)}</span>
                </td>
                <td class="px-4 py-2.5 text-[#e5e7eb] max-w-[240px] truncate">${escapeHtml(uri)}</td>
                <td class="px-4 py-2.5 whitespace-nowrap">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${statusClasses(status)}">${escapeHtml(status)}</span>
                </td>
                <td class="px-4 py-2.5 text-[#9ca3af] whitespace-nowrap">—</td>
                <td class="px-4 py-2.5 text-[#9ca3af] whitespace-nowrap">${escapeHtml(ip)}</td>
                <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                    <span title="${escapeHtml(ua)}">${escapeHtml(uaShort(ua))}</span>
                </td>
            </tr>`;
    }

    function updateBanner() {
        const b = getBanner();
        if (!b) return;
        if (pendingCount > 0) {
            b.style.display = '';
            const c = b.querySelector('[data-new-entries-count]');
            if (c) c.textContent = String(pendingCount);
        } else {
            b.style.display = 'none';
        }
    }

    function resetBanner() {
        pendingCount = 0;
        updateBanner();
    }

    function bindBannerClick() {
        const b = getBanner();
        if (!b || b.dataset.bound === '1') return;
        b.dataset.bound = '1';
        b.addEventListener('click', () => {
            $wire.$refresh();
            resetBanner();
        });
    }

    function prependRows(rows) {
        const tbody = getTbody();
        if (!tbody) return;

        const empty = tbody.querySelector('[data-empty-row]');
        if (empty) empty.remove();

        // Randurile vin DESC (cele mai noi primele). Capturam anchor-ul existent
        // O SINGURA DATA inainte de loop si inseram inaintea lui in ordinea primita,
        // ca sa pastram ordonarea cronologica corecta (newest -> top).
        const { pageSize } = getState();
        const anchor = tbody.firstChild;
        for (const r of rows) {
            if (tbody.querySelector(`[data-log-id="${CSS.escape(String(r.id))}"]`)) continue;
            const tmp = document.createElement('tbody');
            tmp.innerHTML = buildRowHtml(r).trim();
            const newRow = tmp.firstElementChild;
            if (newRow) tbody.insertBefore(newRow, anchor);
        }

        // Taie ultimele daca am depasit page size.
        let rowsNow = tbody.querySelectorAll('tr[data-log-id]');
        while (rowsNow.length > pageSize) {
            tbody.lastElementChild.remove();
            rowsNow = tbody.querySelectorAll('tr[data-log-id]');
        }
    }

    function distributeToSiblings(payload) {
        document.dispatchEvent(new CustomEvent('apache-logs-poll', { detail: payload }));
    }

    const DISABLED_CLS = ['text-[#6b7280]', 'opacity-30', 'cursor-not-allowed', 'pointer-events-none'];
    const ENABLED_CLS  = ['text-label', 'hover:text-text'];

    function setButtonDisabled(btn, disabled) {
        if (!btn) return;
        btn.disabled = disabled;
        if (disabled) {
            ENABLED_CLS.forEach(c => btn.classList.remove(c));
            DISABLED_CLS.forEach(c => btn.classList.add(c));
        } else {
            DISABLED_CLS.forEach(c => btn.classList.remove(c));
            ENABLED_CLS.forEach(c => btn.classList.add(c));
        }
    }

    function updatePagination(logs) {
        if (!logs) return;
        const root = getRoot();
        if (!root) return;

        const summary = root.querySelector('[data-pagination-summary]');
        if (summary) {
            const from = logs.from ?? 0;
            const to   = logs.to   ?? 0;
            summary.textContent = `${from}–${to} of ${Number(logs.total || 0).toLocaleString()}`;
        }

        const page     = parseInt(logs.page      || 1, 10);
        const lastPage = parseInt(logs.last_page || 1, 10);
        setButtonDisabled(root.querySelector('[data-prev-button]'), page <= 1);
        setButtonDisabled(root.querySelector('[data-next-button]'), page >= lastPage);
    }

    function onData(d) {
        const newRows = d.new_logs?.rows || [];
        if (newRows.length > 0) {
            const maxId = Math.max(lastSeenId, ...newRows.map(r => r.id));
            const { currentPage, hasSearch } = getState();

            if (currentPage === 1 && !hasSearch) {
                prependRows(newRows);
            } else {
                pendingCount += newRows.length;
                updateBanner();
            }
            lastSeenId = maxId;
        }

        updatePagination(d.logs);
        distributeToSiblings(d);
    }

    poller = window.createPoller({
        getUrl: () => {
            const { from, to }  = getTimeRange();
            if (!from || !to) return null;
            const s = getState();
            const topIpsTab = document.querySelector('[data-component="top-ips"]')?.dataset.tab || 'All';
            const qs = new URLSearchParams({
                from: String(from),
                to:   String(to),
                page: String(s.currentPage),
                search:       s.searchQuery,
                search_field: s.searchField,
                tab:          topIpsTab,
                since_id:     String(lastSeenId),
            }).toString();
            return `/poll/apache-logs?${qs}`;
        },
        intervalMs: getBucketMs(),
        onData: onData,
    });
    poller.start();

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.id === componentId) {
            refreshLastSeenIdFromDom();
            resetBanner();
            bindBannerClick();
            poller.setInterval(getBucketMs());
            return;
        }
        // Schimbare tab in top-ips-table → abort poll curent (potential cu tab vechi)
        // si porneste unul nou imediat cu data-tab actualizat.
        if (component.name === 'top-ips-table') {
            poller.stop();
            poller.start();
        }
    });

    bindBannerClick();
})();
</script>
@endscript

</div>

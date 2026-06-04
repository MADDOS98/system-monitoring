<div wire:key="top-ips-table"
    x-data="{
        selectedIps: [],
        toggle(ip) {
            if (this.selectedIps.includes(ip)) {
                this.selectedIps = this.selectedIps.filter(i => i !== ip);
            } else {
                this.selectedIps.push(ip);
            }
        }
    }"
    data-component="top-ips"
    data-tab="{{ $this->tab }}"
    data-page="{{ $topIps->currentPage() }}"
    data-bucket-seconds="{{ $bucketSeconds }}"
    class="rounded-lg border border-border overflow-hidden flex flex-col">

    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-2.5 bg-sidebar border-b border-border flex-shrink-0">
        <div class="flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-[#6b7280]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M5 12h14M12 5l7 7-7 7" />
            </svg>
            <span class="text-xs font-mono font-semibold text-[#e5e7eb]">Top source IPs</span>
        </div>
        <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
            @foreach(['All', 'Whitelisted', 'Suspicious'] as $tab)
            <button
                wire:click="setTab('{{ $tab }}')"
                class="px-3 py-1 text-xs font-mono transition-colors duration-150
                        {{ $this->tab === $tab ? 'bg-[#1f2937] text-[#e5e7eb]' : 'text-[#6b7280] hover:text-[#e5e7eb]' }}
                        border-l border-[#2a2a2a] first:border-l-0">
                {{ $tab }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Column headers --}}
    <div class="grid grid-cols-12 px-4 py-2 bg-sidebar border-b border-border flex-shrink-0">
        <div class="col-span-4 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">IP / Host</div>
        <div class="col-span-2 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest">Reqs</div>
        <div class="col-span-2 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-center">Status mix</div>
        <div class="col-span-2 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-center">BW</div>
        <div class="col-span-1 text-[10px] font-mono font-semibold text-[#6b7280] uppercase tracking-widest text-right">Last seen</div>
        <div class="col-span-1"></div>
    </div>

    {{-- Rows --}}
    <div data-rows-container
         class="overflow-y-auto divide-y divide-[#2a2a2a]"
         style="max-height: calc(15 * 54px)">
        @forelse ($topIps as $ip)
        @php
        $showTag  = $ip->status !== null;
        $tagColor = match($ip->status) {
        1 => 'bg-green-900 text-green-400',
        2 => 'bg-yellow-950 text-yellow-400',
        3 => 'bg-red-900 text-red-400',
        default => '',
        };
        $s2 = $ip->s2xx; $s3 = $ip->s3xx; $s4 = $ip->s4xx; $s5 = $ip->s5xx;
        $p2 = $s2; $p3 = $p2 + $s3; $p4 = $p3 + $s4; $p5 = $p4 + $s5;
        $gradient = "linear-gradient(to right, #22c55e 0% {$p2}%, #3b82f6 {$p2}% {$p3}%, #eab308 {$p3}% {$p4}%, #ef4444 {$p4}% {$p5}%)";
        $rowBgSelected = match($ip->status) {
        1 => 'bg-green-500/15',
        2 => 'bg-yellow-500/15',
        3 => 'bg-red-500/15',
        default => 'bg-[#111111]',
        };
        $rowBgIdle = match($ip->status) {
        1 => 'bg-green-500/5',
        2 => 'bg-yellow-500/5',
        3 => 'bg-red-500/5',
        default => 'bg-[#111111]',
        };
        @endphp
        <div class="relative grid grid-cols-12 px-4 py-2.5 transition-colors duration-100 items-center group"
             :class="selectedIps.includes('{{ $ip->ip }}') ? '{{ $rowBgSelected }}' : '{{ $rowBgIdle }}'">
            {{-- Overlay transparent care intensifica bg-ul existent la hover --}}
            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-[0.04] transition-opacity duration-100 pointer-events-none"></div>

            {{-- IP + tag + hostname --}}
            <div class="col-span-4">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[#e5e7eb] text-xs font-mono">{{ $ip->ip }}</span>
                    @if($showTag)
                    <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded {{ $tagColor }}">{{ $ip->tag }}</span>
                    @endif
                </div>
                @if($showTag && $ip->host)
                <div class="text-[10px] text-[#6b7280] font-mono mt-0.5 truncate">{{ $ip->host }}</div>
                @endif
            </div>

            {{-- Reqs --}}
            <div class="col-span-2 text-xs font-mono text-[#e5e7eb]">
                {{ number_format($ip->reqs) }}
            </div>

            {{-- Status mix gradient bar --}}
            <div class="col-span-2 pr-2">
                <div class="w-full rounded-full h-1.5" style="background: {{ $gradient }}"></div>
            </div>

            {{-- BW --}}
            <div class="col-span-2 text-xs font-mono text-[#9ca3af] text-center">
                @php
                $bytes = $ip->total_bytes ?? 0;
                if ($bytes >= 1073741824) echo round($bytes / 1073741824, 1) . ' GB';
                elseif ($bytes >= 1048576) echo round($bytes / 1048576, 1) . ' MB';
                elseif ($bytes >= 1024) echo round($bytes / 1024, 1) . ' KB';
                else echo $bytes . ' B';
                @endphp
            </div>

            {{-- Last seen --}}
            <div class="col-span-1 text-[10px] font-mono text-[#6b7280] text-right">
                {{ $ip->last_seen ?? '—' }}
            </div>

            {{-- Select button --}}
            <div class="col-span-1 flex justify-end">
                <button
                    type="button"
                    @click="toggle('{{ $ip->ip }}')"
                    class="w-6 h-6 rounded border flex items-center justify-center transition-colors duration-150"
                    :class="selectedIps.includes('{{ $ip->ip }}')
                        ? 'border-accent bg-accent/20 text-accent'
                        : 'border-[#3a3a3a] bg-[#1a1a1a] text-[#6b7280] hover:border-accent hover:text-accent'">
                    {{-- Checkmark when selected --}}
                    <svg x-show="selectedIps.includes('{{ $ip->ip }}')" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M20 6L9 17l-5-5" />
                    </svg>
                    {{-- Plus when not --}}
                    <svg x-show="!selectedIps.includes('{{ $ip->ip }}')" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                </button>
            </div>

        </div>
        @empty
        <div class="flex items-center justify-center h-full text-[#6b7280] text-xs font-mono">
            No data available.
        </div>
        @endforelse
    </div>

    {{-- Pagination footer — randat mereu; butoanele dezactivate cand nu sunt necesare. --}}
    @php
        $isFirst         = $topIps->onFirstPage();
        $isLast          = ! $topIps->hasMorePages();
        $disabledClasses = 'text-[#6b7280] opacity-30 cursor-not-allowed pointer-events-none';
        $enabledClasses  = 'text-label hover:text-text';
    @endphp
    <div data-pagination class="px-4 py-2.5 border-t border-border bg-sidebar flex items-center justify-between flex-shrink-0">

        <button
            data-prev-button
            wire:click="previousPage"
            wire:loading.attr="disabled"
            @if($isFirst) disabled @endif
            class="px-3 py-1 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isFirst ? $disabledClasses : $enabledClasses }}">
            ← Newer
        </button>

        <span data-pagination-summary class="text-xs font-mono text-[#6b7280]">
            {{ $topIps->firstItem() ?? 0 }}–{{ $topIps->lastItem() ?? 0 }} of {{ $topIps->total() }}
        </span>

        <button
            data-next-button
            wire:click="nextPage"
            wire:loading.attr="disabled"
            @if($isLast) disabled @endif
            class="px-3 py-1 bg-panel border border-border rounded text-xs font-mono transition-colors duration-150 {{ $isLast ? $disabledClasses : $enabledClasses }}">
            Older →
        </button>

    </div>

@script
<script>
(function () {
    const componentId = '{{ $this->getId() }}';
    function getRoot() { return document.querySelector('[wire\\:id="' + componentId + '"]'); }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    function formatBytes(b) {
        b = b || 0;
        if (b >= 1073741824) return (Math.round(b / 1073741824 * 10) / 10) + ' GB';
        if (b >= 1048576)    return (Math.round(b / 1048576 * 10) / 10) + ' MB';
        if (b >= 1024)       return (Math.round(b / 1024 * 10) / 10) + ' KB';
        return b + ' B';
    }

    function tagClasses(status) {
        switch (status) {
            case 1: return 'bg-green-900 text-green-400';
            case 2: return 'bg-yellow-950 text-yellow-400';
            case 3: return 'bg-red-900 text-red-400';
            default: return '';
        }
    }

    function rowBgIdle(status) {
        switch (status) {
            case 1: return 'bg-green-500/5';
            case 2: return 'bg-yellow-500/5';
            case 3: return 'bg-red-500/5';
            default: return 'bg-[#111111]';
        }
    }

    function buildIpRow(ip) {
        const s2 = ip.s2xx, s3 = ip.s3xx, s4 = ip.s4xx, s5 = ip.s5xx;
        const p2 = s2;
        const p3 = p2 + s3;
        const p4 = p3 + s4;
        const p5 = p4 + s5;
        const gradient = `linear-gradient(to right, #22c55e 0% ${p2}%, #3b82f6 ${p2}% ${p3}%, #eab308 ${p3}% ${p4}%, #ef4444 ${p4}% ${p5}%)`;
        const showTag = ip.status !== null && ip.status !== undefined;
        return `
            <div class="relative grid grid-cols-12 px-4 py-2.5 transition-colors duration-100 items-center group ${rowBgIdle(ip.status)}">
                <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-[0.04] transition-opacity duration-100 pointer-events-none"></div>
                <div class="col-span-4">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-[#e5e7eb] text-xs font-mono">${escapeHtml(ip.ip)}</span>
                        ${showTag ? `<span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded ${tagClasses(ip.status)}">${escapeHtml(ip.tag)}</span>` : ''}
                    </div>
                    ${showTag && ip.host ? `<div class="text-[10px] text-[#6b7280] font-mono mt-0.5 truncate">${escapeHtml(ip.host)}</div>` : ''}
                </div>
                <div class="col-span-2 text-xs font-mono text-[#e5e7eb]">${Number(ip.reqs).toLocaleString()}</div>
                <div class="col-span-2 pr-2">
                    <div class="w-full rounded-full h-1.5" style="background: ${gradient}"></div>
                </div>
                <div class="col-span-2 text-xs font-mono text-[#9ca3af] text-center">${escapeHtml(formatBytes(ip.total_bytes))}</div>
                <div class="col-span-1 text-[10px] font-mono text-[#6b7280] text-right">${escapeHtml(ip.last_seen ?? '—')}</div>
                <div class="col-span-1"></div>
            </div>`;
    }

    function setButtonDisabled(btn, disabled) {
        if (!btn) return;
        btn.toggleAttribute('disabled', disabled);
        const dis = ['opacity-30', 'cursor-not-allowed', 'pointer-events-none', 'text-[#6b7280]'];
        const en  = ['text-label', 'hover:text-text'];
        if (disabled) {
            dis.forEach(c => btn.classList.add(c));
            en.forEach(c  => btn.classList.remove(c));
        } else {
            dis.forEach(c => btn.classList.remove(c));
            en.forEach(c  => btn.classList.add(c));
        }
    }

    function updateTopIps(payload) {
        const root = getRoot();
        if (!root) return;
        const container = root.querySelector('[data-rows-container]');
        if (!container) return;

        // Acceptam si payload-ul vechi (array flat) pentru robustete, dar noul format e object.
        const rows = Array.isArray(payload)
            ? payload
            : (Array.isArray(payload?.rows) ? payload.rows : []);

        if (rows.length === 0) {
            container.innerHTML = '<div class="flex items-center justify-center h-full text-[#6b7280] text-xs font-mono">No data available.</div>';
        } else {
            container.innerHTML = rows.map(buildIpRow).join('');
        }

        // Daca payload-ul e in noul format object, updateaza footer-ul + butoanele
        if (!Array.isArray(payload) && payload) {
            const summary = root.querySelector('[data-pagination-summary]');
            if (summary) {
                const f = payload.from ?? 0;
                const t = payload.to   ?? 0;
                summary.textContent = `${f}–${t} of ${payload.total ?? 0}`;
            }

            const currentPage = payload.page      ?? 1;
            const lastPage    = payload.last_page ?? 1;
            setButtonDisabled(root.querySelector('[data-prev-button]'), currentPage <= 1);
            setButtonDisabled(root.querySelector('[data-next-button]'), currentPage >= lastPage);

            // Mentine sincronizat data-page pentru poll-urile urmatoare
            root.dataset.page = String(currentPage);
        }
    }

    document.addEventListener('apache-logs-poll', (e) => {
        if (!e.detail) return;
        updateTopIps(e.detail.top_ips);
    });
})();
</script>
@endscript

</div>
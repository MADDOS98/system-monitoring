<div wire:key="peak-traffic-timeline"
     data-bins='@json($bins)'
     data-levels='@json($levels)'
     data-hours='@json($hours)'
     data-max="{{ $max }}"
     data-start="{{ $start }}"
     data-end="{{ $end }}"
     class="w-full rounded-lg border border-[#2a2a2a] mb-5 px-5 pt-4 pb-3">

    {{-- Header --}}
    <div class="mb-4">
        <p class="text-xs font-mono font-semibold text-[#e5e7eb]">Peak traffic timeline</p>
        <p class="text-[11px] font-mono text-[#6b7280] mt-0.5">
            24 bins &middot; last 24h &middot;
            <span data-selected-label class="text-[#93c5fd]" style="display:none"></span>
            <span data-default-label>click to inspect</span>
        </p>
    </div>

    {{-- Bars --}}
    <div data-bars-container class="flex items-end gap-[3px]" style="height: 120px">
        @for ($h = 0; $h < 24; $h++)
            @php
            $count    = $bins[$h];
            $pct      = $max > 0 ? ($count / $max) : 0;
            $heightPx = max(3, (int) round($pct * 108));
            $barLevel = $levels[$h];
            $hourLbl  = $hours[$h];
            $barIdle  = match($barLevel) {
                'warning'  => 'bg-orange-600 group-hover:bg-orange-500',
                'critical' => 'bg-red-700 group-hover:bg-red-600',
                default    => 'bg-blue-700 group-hover:bg-blue-600',
            };
            @endphp

            <div data-bar-cell data-hour="{{ $h }}"
                class="relative flex-1 flex flex-col justify-end group cursor-pointer"
                style="height: 108px">
                {{-- Tooltip --}}
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 z-10
                            pointer-events-none opacity-0 group-hover:opacity-100
                            transition-opacity duration-150 whitespace-nowrap">
                    <div class="bg-[#1a1a1a] border border-[#3a3a3a] rounded px-2 py-1 text-[11px] font-mono text-[#e5e7eb]">
                        <span data-tooltip-hour>{{ $hourLbl }}</span> &mdash; <span data-tooltip-count>{{ number_format($count) }}</span> req
                    </div>
                    <div class="w-2 h-2 bg-[#1a1a1a] border-r border-b border-[#3a3a3a] rotate-45 mx-auto -mt-1"></div>
                </div>

                {{-- Bar --}}
                <div data-bar
                    data-level="{{ $barLevel }}"
                    style="height: {{ $heightPx }}px"
                    class="w-full rounded-sm transition-all duration-200 {{ $barIdle }}"></div>
            </div>
        @endfor
    </div>

    {{-- Hour labels — orele REALE locale ale ferestrei (rotesc cu trecerea timpului in live). --}}
    <div class="flex gap-[3px] mt-1.5">
        @for ($h = 0; $h < 24; $h++)
            <div class="flex-1 text-center text-[11px] font-mono text-gray-400"
                 data-hour-label data-hour="{{ $h }}">
                {{ $hours[$h] }}
            </div>
        @endfor
    </div>

@script
<script>
(function () {
    const componentId = '{{ $this->getId() }}';
    function getRoot() { return document.querySelector('[wire\\:id="' + componentId + '"]'); }

    const BAR_IDLE = {
        warning:  'bg-orange-600 group-hover:bg-orange-500',
        critical: 'bg-red-700 group-hover:bg-red-600',
        normal:   'bg-blue-700 group-hover:bg-blue-600',
    };
    const BAR_ACTIVE = {
        warning:  'bg-orange-400',
        critical: 'bg-red-500',
        normal:   'bg-blue-500',
    };
    const ALL_BAR_CLASSES = Object.values(BAR_IDLE).concat(Object.values(BAR_ACTIVE))
        .flatMap(c => c.split(' '));

    let selectedHour = null;

    function getBins()   { try { return JSON.parse(getRoot()?.dataset.bins   || '[]'); } catch (e) { return []; } }
    function getHours()  { try { return JSON.parse(getRoot()?.dataset.hours  || '[]'); } catch (e) { return []; } }

    function paintBar(barEl, level, active) {
        ALL_BAR_CLASSES.forEach(c => barEl.classList.remove(c));
        const cls = (active ? BAR_ACTIVE[level] : BAR_IDLE[level]) || (active ? BAR_ACTIVE.normal : BAR_IDLE.normal);
        cls.split(' ').forEach(c => barEl.classList.add(c));
        barEl.dataset.level = level;
    }

    function applyData(bins, levels, max, hours) {
        const root = getRoot();
        if (!root) return;
        root.dataset.bins   = JSON.stringify(bins);
        root.dataset.levels = JSON.stringify(levels);
        root.dataset.hours  = JSON.stringify(hours);
        root.dataset.max    = String(max);

        const cells = root.querySelectorAll('[data-bar-cell]');
        cells.forEach((cell) => {
            const h     = parseInt(cell.dataset.hour, 10);
            const count = bins[h] ?? 0;
            const lvl   = levels[h] ?? 'normal';
            const hr    = hours[h] ?? '';
            const pct   = max > 0 ? count / max : 0;
            const px    = Math.max(3, Math.round(pct * 108));

            const bar = cell.querySelector('[data-bar]');
            if (bar) {
                bar.style.height = px + 'px';
                paintBar(bar, lvl, selectedHour === h);
            }
            const tt = cell.querySelector('[data-tooltip-count]');
            if (tt) tt.textContent = Number(count).toLocaleString();
            const tth = cell.querySelector('[data-tooltip-hour]');
            if (tth) tth.textContent = hr;
        });

        // Hour labels se rotesc odata cu trecerea timpului in live mode.
        root.querySelectorAll('[data-hour-label]').forEach((lbl) => {
            const h = parseInt(lbl.dataset.hour, 10);
            lbl.textContent = hours[h] ?? '';
        });

        updateSelectedLabel();
    }

    function updateSelectedLabel() {
        const root  = getRoot();
        const sel   = root?.querySelector('[data-selected-label]');
        const def   = root?.querySelector('[data-default-label]');
        if (!sel || !def) return;
        if (selectedHour === null) {
            sel.style.display = 'none';
            def.style.display = '';
            return;
        }
        const bins  = getBins();
        const hours = getHours();
        const count = bins[selectedHour] ?? 0;
        const hr    = hours[selectedHour] ?? (String(selectedHour).padStart(2, '0') + ':00');
        sel.textContent   = hr + ' — ' + Number(count).toLocaleString() + ' requests';
        sel.style.display = '';
        def.style.display = 'none';
    }

    function onBarClick(e) {
        const cell = e.target.closest('[data-bar-cell]');
        if (!cell) return;
        const h = parseInt(cell.dataset.hour, 10);
        if (selectedHour === h) {
            selectedHour = null;
        } else {
            // Repaint previous selected
            if (selectedHour !== null) {
                const prevCell = getRoot()?.querySelector(`[data-bar-cell][data-hour="${selectedHour}"]`);
                const prevBar  = prevCell?.querySelector('[data-bar]');
                if (prevBar) paintBar(prevBar, prevBar.dataset.level || 'normal', false);
            }
            selectedHour = h;
        }
        const bar = cell.querySelector('[data-bar]');
        if (bar) paintBar(bar, bar.dataset.level || 'normal', selectedHour === h);
        updateSelectedLabel();
    }

    function bindClicks() {
        const root = getRoot();
        if (!root || root.dataset.peakBound === '1') return;
        root.dataset.peakBound = '1';
        root.querySelector('[data-bars-container]')?.addEventListener('click', onBarClick);
    }
    bindClicks();

    document.addEventListener('apache-logs-poll', (e) => {
        if (!e.detail?.peak) return;
        const p = e.detail.peak;
        const binsArr  = Array.isArray(p.bins)   ? p.bins   : Object.values(p.bins);
        const lvlArr   = Array.isArray(p.levels) ? p.levels : Object.values(p.levels);
        const hoursArr = Array.isArray(p.hours)  ? p.hours  : Object.values(p.hours);
        applyData(binsArr, lvlArr, p.max, hoursArr);
    });

    Livewire.hook('morph.updated', ({ component }) => {
        if (component.id !== componentId) return;
        // Reset bind dacă morph a re-randat root-ul.
        const root = getRoot();
        if (root) delete root.dataset.peakBound;
        bindClicks();
    });
})();
</script>
@endscript

</div>

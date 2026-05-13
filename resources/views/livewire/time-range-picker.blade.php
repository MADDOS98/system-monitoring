<div
    wire:key="time-range-picker"
    data-live="{{ $live ? '1' : '0' }}"
    data-preset="{{ $preset }}"
    class="flex items-start justify-between mb-5">

    {{-- Left: title + window --}}
    <div>
        <h1 class="text-xl font-semibold text-text">{{ $title }}</h1>
        <p class="text-xs text-muted font-mono mt-1">
            Window:
            <span data-window-from class="text-label">
                {{ $from ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $from)->format('Y-m-d H:i') : '—' }}
            </span>
            <span class="mx-1">—</span>
            <span data-window-to class="text-label">
                {{ $to ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $to)->format('Y-m-d H:i') : '—' }}
            </span>
            @if($live)
                <span class="ml-2 inline-flex items-center gap-1 text-emerald-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    live
                </span>
            @endif
        </p>
    </div>

    {{-- Right: controls --}}
    <div class="flex items-center gap-2">

        {{-- From input --}}
        <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5">
            <span class="text-xs text-muted font-mono">From</span>
            <input
                data-from-input
                type="datetime-local"
                wire:model.live="from"
                class="bg-transparent text-xs text-text font-mono border-none outline-none focus:ring-0 p-0" />
        </div>

        {{-- To input — numai pe custom --}}
        @if($preset === 'custom')
        <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5">
            <span class="text-xs text-muted font-mono">To</span>
            <input
                data-to-input
                type="datetime-local"
                wire:model.live="to"
                class="bg-transparent text-xs text-text font-mono border-none outline-none focus:ring-0 p-0" />
        </div>
        @endif

        {{-- Now button--}}
        <button
            wire:click="setNow"
            class="px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150">
            Now
        </button>

        {{-- Preset buttons --}}
        <div class="flex items-center bg-panel border border-border rounded-md overflow-hidden">
            @foreach(['5m', '1h', '24h', 'custom'] as $p)
            <button
                wire:click="applyPreset('{{ $p }}')"
                class="px-3 py-1.5 text-xs font-mono transition-colors duration-150 border-x border-[#2a2a2a]
                        {{ $preset === $p ? 'bg-[#1f2937] text-[#e5e7eb]' : 'text-[#6b7280] hover:text-[#e5e7eb]' }}">
                {{ $p }}
            </button>
            @endforeach
        </div>

    </div>
</div>

@script
<script>
    (function () {
        const componentId = '{{ $this->getId() }}';
        const PRESET_MINUTES = { '5m': 5, '1h': 60, '24h': 1440 };

        function pad(n) { return String(n).padStart(2, '0'); }

        function formatYmdHm(d) {
            return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }

        function formatDatetimeLocal(d) {
            return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
        }

        function getState() {
            const el = document.querySelector('[wire\\:id="' + componentId + '"]');
            return {
                live:   el?.dataset.live === '1',
                preset: el?.dataset.preset || '',
                el,
            };
        }

        function tick() {
            const { live, preset, el } = getState();
            if (!live || !el) return;

            const minutes = PRESET_MINUTES[preset];
            if (!minutes) return;

            const now  = new Date();
            const from = new Date(now.getTime() - minutes * 60 * 1000);

            // Display text (Window: ... — ...)
            const fromEl = el.querySelector('[data-window-from]');
            const toEl   = el.querySelector('[data-window-to]');
            if (fromEl) fromEl.textContent = formatYmdHm(from);
            if (toEl)   toEl.textContent   = formatYmdHm(now);

            // Input "From" — value setat programatic NU declanseaza event,
            // deci wire:model.live nu sincronizeaza, $live ramane true.
            // Nu suprascriem cand inputul are focus (user-ul ar putea sa scrie).
            const fromInput = el.querySelector('[data-from-input]');
            if (fromInput && document.activeElement !== fromInput) {
                fromInput.value = formatDatetimeLocal(from);
            }
        }

        setInterval(tick, 1000);
        tick();
    })();
</script>
@endscript

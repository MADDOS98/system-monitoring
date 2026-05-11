<div class="flex items-start justify-between mb-5">

    {{-- Left: title + window --}}
    <div>
        <h1 class="text-xl font-semibold text-text">{{ $title }}</h1>
        <p class="text-xs text-muted font-mono mt-1">
            Window:
            <span class="text-label">
                {{ $from ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $from)->format('Y-m-d H:i') : '—' }}
            </span>
            <span class="mx-1">—</span>
            <span class="text-label">
                {{ $to ? \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $to)->format('Y-m-d H:i') : '—' }}
            </span>
        </p>
    </div>

    {{-- Right: controls --}}
    <div class="flex items-center gap-2">

        {{-- From input --}}
        <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5">
            <span class="text-xs text-muted font-mono">From</span>
            <input
                type="datetime-local"
                wire:model.live="from"
                class="bg-transparent text-xs text-text font-mono border-none outline-none focus:ring-0 p-0" />
        </div>

        {{-- To input — numai pe custom --}}
        @if($preset === 'custom')
        <div class="flex items-center gap-2 bg-panel border border-border rounded-md px-3 py-1.5">
            <span class="text-xs text-muted font-mono">To</span>
            <input
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

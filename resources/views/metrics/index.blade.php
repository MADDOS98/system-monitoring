<x-app-layout>

    <livewire:time-range-picker title="Metrics" />

    {{-- Tabs --}}
    <div x-data="{ tab: 'cpu' }">

        <div class="border-b border-border mb-6">
            <nav class="-mb-px flex gap-1">

                @foreach([
                    ['id' => 'cpu',     'label' => 'CPU',     'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>'],
                    ['id' => 'ram',     'label' => 'RAM',     'icon' => '<rect x="2" y="8" width="20" height="8" rx="2"/><path d="M6 8V6M10 8V6M14 8V6M18 8V6M6 16v2M10 16v2M14 16v2M18 16v2"/>'],
                    ['id' => 'network', 'label' => 'Network', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20M12 2a15.3 15.3 0 0 0 0 20"/>'],
                    ['id' => 'disk',    'label' => 'Disk',    'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>'],
                ] as $t)
                <button
                    @click="tab = '{{ $t['id'] }}'"
                    :class="tab === '{{ $t['id'] }}'
                        ? 'border-b-2 border-[#129247] text-text'
                        : 'border-b-2 border-transparent text-muted hover:text-label hover:border-border'"
                    class="flex items-center gap-2 px-4 py-3 text-sm font-mono transition-colors duration-150 whitespace-nowrap">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        {!! $t['icon'] !!}
                    </svg>
                    {{ $t['label'] }}
                </button>
                @endforeach

            </nav>
        </div>

        <div x-show="tab === 'cpu'">
            {{-- CPU charts --}}
        </div>
        <div x-show="tab === 'ram'">
            <livewire:ram-metrics />
        </div>
        <div x-show="tab === 'network'">
            {{-- Network charts --}}
        </div>
        <div x-show="tab === 'disk'">
            {{-- Disk charts --}}
        </div>

    </div>

</x-app-layout>

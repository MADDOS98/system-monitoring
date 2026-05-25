<x-app-layout>

    <livewire:time-range-picker title="Metrics" />

    {{-- Tabs --}}
    <div>

        <div class="border-b border-border mb-6">
            <nav class="-mb-px flex gap-1">

                @foreach([
                    ['id' => 'cpu',     'label' => 'CPU',     'icon' => '<circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>'],
                    ['id' => 'ram',     'label' => 'RAM',     'icon' => '<rect x="2" y="8" width="20" height="8" rx="2"/><path d="M6 8V6M10 8V6M14 8V6M18 8V6M6 16v2M10 16v2M14 16v2M18 16v2"/>'],
                    ['id' => 'network', 'label' => 'Network', 'icon' => '<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 0 20M12 2a15.3 15.3 0 0 0 0 20"/>'],
                    ['id' => 'disk',    'label' => 'Disk',    'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4.03 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4.03 3 9 3s9-1.34 9-3"/>'],
                ] as $t)
                <a
                    href="{{ route('metrics', ['tab' => $t['id']]) }}"
                    wire:navigate
                    class="flex items-center gap-2 px-4 py-3 text-sm font-mono transition-colors duration-150 whitespace-nowrap
                        {{ $tab === $t['id']
                            ? 'border-b-2 border-[#129247] text-text'
                            : 'border-b-2 border-transparent text-muted hover:text-label hover:border-border' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        {!! $t['icon'] !!}
                    </svg>
                    {{ $t['label'] }}
                </a>
                @endforeach

            </nav>
        </div>

        {{-- Alerts per Tab --}}
        <livewire:tab-alerts :tab="$tab" :key="'tab-alerts-' . $tab" />

        {{-- Tab Content --}}
        @switch($tab)
            @case('cpu')     <livewire:cpu-metrics />     @break
            @case('ram')     <livewire:ram-metrics />     @break
            @case('network') <livewire:network-metrics /> @break
            @case('disk')    <livewire:disk-metrics />    @break
        @endswitch

        

    </div>

</x-app-layout>

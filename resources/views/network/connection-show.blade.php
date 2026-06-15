<x-app-layout>

    {{-- Breadcrumb-like back link --}}
    <div class="mb-3">
        <a href="{{ route('metrics', ['tab' => 'network']) }}" wire:navigate
           class="inline-flex items-center gap-1.5 text-xs font-mono text-muted hover:text-text transition-colors">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Network
        </a>
    </div>

    <livewire:time-range-picker :title="'Connections: ' . $key" />

    <livewire:connection-chart :name="$key" :key="'connection-chart-' . $key" />

</x-app-layout>

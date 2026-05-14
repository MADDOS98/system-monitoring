<x-app-layout>

    
    <div class="flex items-start gap-3">
        {{-- Time Range Picker — componentă Livewire, dispatchează timeRangeChanged --}}
        <div class="flex-1 min-w-0">
            <livewire:time-range-picker title="Apache Traffic" />
        </div>

        <button
            type="button"
            class="px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150 whitespace-nowrap">
            IP Reputations
        </button>
    </div>

    {{-- Peak Traffic Timeline --}}
    <livewire:peak-traffic-timeline />

    {{-- Tabel principal cu search + paginare — componentă Livewire --}}
    <div class="w-full rounded-lg border border-border overflow-hidden">
        <livewire:apache-logs-table />
    </div>

    {{-- Tabelele de jos — side by side, aceeași înălțime --}}
    <div class="mt-5 grid grid-cols-2 gap-4">
        <livewire:top-ips-table />
        <livewire:status-table />
    </div>

</x-app-layout>
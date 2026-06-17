<x-app-layout>

    
    <div class="flex items-start gap-3">
        {{-- Time Range Picker — componentă Livewire, dispatchează timeRangeChanged --}}
        <div class="flex-1 min-w-0">
            <livewire:time-range-picker title="Apache Traffic" />
        </div>

        <button
            type="button"
            onclick="Livewire.dispatch('open-host-reputations')"
            class="px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150 whitespace-nowrap">
            IP Reputations
        </button>
    </div>

    {{-- Modal IP Reputations (mounted gol, devine vizibil cand butonul de mai sus dispatch-uieaza eventul) --}}
    <livewire:apache-logs.host-reputations />

    {{-- Peak Traffic Timeline --}}
    <livewire:apache-logs.peak-traffic-timeline />

    {{-- Tabel principal cu search + paginare — componentă Livewire --}}
    <div class="w-full rounded-lg border border-border overflow-hidden">
        <livewire:apache-logs.apache-logs-table />
    </div>

    {{-- Tabelele de jos — side by side, aceeași înălțime --}}
    <div class="mt-5 grid grid-cols-2 gap-4">
        <livewire:apache-logs.top-ips-table />
        <livewire:apache-logs.status-table />
    </div>

</x-app-layout>
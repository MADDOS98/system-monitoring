<x-app-layout>

    {{-- Time Range Picker — componentă Livewire, dispatchează timeRangeChanged --}}
    <livewire:time-range-picker />

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
<x-app-layout>

    @php
        $activeCount = \App\Models\Alert::whereNull('read_at')->count();
        $readCount   = \App\Models\Alert::whereNotNull('read_at')->count();
    @endphp

    {{-- Page header --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Alerts</h1>
            <p class="text-sm text-muted mt-0.5 font-mono">
                Real-time monitoring alerts ·
                <span class="text-text">{{ $activeCount }} active</span>
                ·
                <span class="text-muted">{{ $readCount }} read</span>
            </p>
        </div>
    </div>

    <livewire:alerts-list />

</x-app-layout>

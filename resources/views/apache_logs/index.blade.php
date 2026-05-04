<x-app-layout>

    {{-- Header --}}
    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-text">Apache Logs</h1>
            <p class="text-sm text-muted mt-0.5 font-mono">
                Access log for <span class="font-semibold text-text">web-prod-01</span>
                · {{ $logs ? count($logs) : 0 }} entries
            </p>
        </div>

        {{-- Filters --}}
        <div class="flex items-center gap-2">

            {{-- Status filter --}}
            <select class="bg-panel border border-border text-label text-xs font-mono rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent transition duration-150">
                <option value="">All statuses</option>
                <option value="200">200 OK</option>
                <option value="301">301 Redirect</option>
                <option value="404">404 Not Found</option>
                <option value="500">500 Error</option>
            </select>

            {{-- Method filter --}}
            <select class="bg-panel border border-border text-label text-xs font-mono rounded-md px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-accent focus:border-accent transition duration-150">
                <option value="">All methods</option>
                <option value="GET">GET</option>
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="DELETE">DELETE</option>
            </select>

            {{-- Refresh --}}
            <button class="flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-label hover:text-text font-mono transition-colors duration-150">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38"/>
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="w-full rounded-lg border border-border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs font-mono">

                {{-- Head --}}
                <thead>
                    <tr class="bg-sidebar border-b border-border">
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Time</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Remote Host</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Method</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">URI</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Protocol</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Status</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Bytes</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Referer</th>
                        <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">User Agent</th>
                    </tr>
                </thead>

                {{-- Body --}}
                <tbody class="divide-y divide-border">
                    @forelse ($logs as $log)
                        <tr class="bg-main hover:bg-panel transition-colors duration-100 group">

                            {{-- Time --}}
                            <td class="px-4 py-2.5 text-muted whitespace-nowrap">
                                {{ date('Y-m-d H:i:s', $log->log_time) }}
                            </td>

                            {{-- Remote Host --}}
                            <td class="px-4 py-2.5 text-label whitespace-nowrap">
                                {{ $log->remote_host }}
                            </td>

                            {{-- Method --}}
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @php
                                    $methodColor = match($log->method) {
                                        'GET'    => 'text-[#22c55e]',
                                        'POST'   => 'text-[#3b82f6]',
                                        'PUT', 'PATCH' => 'text-[#f59e0b]',
                                        'DELETE' => 'text-[#ef4444]',
                                        default  => 'text-muted',
                                    };
                                @endphp
                                <span class="{{ $methodColor }} font-semibold">{{ $log->method }}</span>
                            </td>

                            {{-- URI --}}
                            <td class="px-4 py-2.5 text-text max-w-[200px] truncate" title="{{ $log->uri }}">
                                {{ $log->uri }}
                            </td>

                            {{-- Protocol --}}
                            <td class="px-4 py-2.5 text-muted whitespace-nowrap">
                                {{ $log->protocol }}
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @php
                                    $statusColor = match(true) {
                                        $log->status >= 500 => 'bg-danger/10 text-danger',
                                        $log->status >= 400 => 'bg-warning/10 text-warning',
                                        $log->status >= 300 => 'bg-accent/10 text-accent',
                                        $log->status >= 200 => 'bg-live/10 text-live',
                                        default             => 'bg-border text-muted',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $statusColor }}">
                                    {{ $log->status }}
                                </span>
                            </td>

                            {{-- Bytes --}}
                            <td class="px-4 py-2.5 text-muted whitespace-nowrap">
                                {{ number_format($log->bytes_sent) }}
                            </td>

                            {{-- Referer --}}
                            <td class="px-4 py-2.5 text-muted max-w-[150px] truncate" title="{{ $log->referer }}">
                                {{ $log->referer === '-' ? '—' : $log->referer }}
                            </td>

                            {{-- User Agent --}}
                            <td class="px-4 py-2.5 text-muted max-w-[200px] truncate" title="{{ $log->user_agent }}">
                                {{ $log->user_agent }}
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-muted font-mono">
                                No log entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        @if ($logs instanceof \Illuminate\Pagination\LengthAwarePaginator && $logs->hasPages())
            <div class="px-4 py-3 border-t border-border bg-sidebar flex items-center justify-between">
                <p class="text-xs text-muted font-mono">
                    Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
                </p>
                <div class="text-xs font-mono">
                    {{ $logs->links() }}
                </div>
            </div>
        @endif

    </div>

</x-app-layout>
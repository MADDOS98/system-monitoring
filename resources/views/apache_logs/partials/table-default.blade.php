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
        <tr class="bg-main hover:bg-panel transition-colors duration-100">

            <td class="px-4 py-2.5 text-muted whitespace-nowrap">
                {{ date('Y-m-d H:i:s', $log->log_time) }}
            </td>

            <td class="px-4 py-2.5 text-label whitespace-nowrap">
                {{ $log->remote_host }}
            </td>

            <td class="px-4 py-2.5 whitespace-nowrap">
                @php
                $methodColor = match($log->method) {
                'GET' => 'text-accent',
                'POST' => 'text-live',
                'PUT', 'PATCH' => 'text-warning',
                'DELETE' => 'text-danger',
                default => 'text-muted',
                };
                @endphp
                <span class="{{ $methodColor }} font-semibold">{{ $log->method }}</span>
            </td>

            <td class="px-4 py-2.5 text-text max-w-[200px] truncate" title="{{ $log->uri }}">
                {{ $log->uri }}
            </td>

            <td class="px-4 py-2.5 text-muted whitespace-nowrap">
                {{ $log->protocol }}
            </td>

            <td class="px-4 py-2.5 whitespace-nowrap">
                @php
                $statusColor = match(true) {
                $log->status >= 500 => 'bg-danger/10 text-danger',
                $log->status >= 400 => 'bg-warning/10 text-warning',
                $log->status >= 300 => 'bg-accent/10 text-live',
                $log->status >= 200 => 'bg-live/10 text-accent',
                default => 'bg-border text-muted',
                };
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $statusColor }}">
                    {{ $log->status }}
                </span>
            </td>

            <td class="px-4 py-2.5 text-muted whitespace-nowrap">
                {{ number_format($log->bytes_sent) }}
            </td>

            <td class="px-4 py-2.5 text-muted max-w-[150px] truncate" title="{{ $log->referer }}">
                {{ $log->referer === '-' ? '—' : $log->referer }}
            </td>

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
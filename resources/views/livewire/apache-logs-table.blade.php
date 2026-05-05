<div wire:key="apache-logs-table">

    <table class="w-full text-xs font-mono">
        <thead>
            <tr class="bg-sidebar border-b border-border">
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Time</th>
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Method</th>
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Path</th>
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Status</th>
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">IP</th>
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">UA</th>
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Bytes</th>
                <th class="px-4 py-3 text-left text-[#6b7280] uppercase tracking-widest font-semibold whitespace-nowrap">Referer</th>
            </tr>
        </thead>
    </table>

    <div class="overflow-x-auto overflow-y-auto" style="max-height: calc(10 * 41px)">
        <table class="w-full text-xs font-mono">
            <tbody class="divide-y divide-[#2a2a2a]">
                @forelse ($logs as $log)
                    <tr class="bg-[#111111] hover:bg-[#161616] transition-colors duration-100">

                        <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                            {{ isset($log->log_time) ? date('H:i:s', $log->log_time) : '—' }}
                        </td>

                        <td class="px-4 py-2.5 whitespace-nowrap">
                            @php
                                $mc = match($log->method ?? '') {
                                    'GET' => 'bg-blue-950 text-blue-400',
                                    'POST' => 'bg-green-950 text-green-400',
                                    'PUT', 'PATCH' => 'bg-yellow-950 text-yellow-400',
                                    'DELETE' => 'bg-red-950 text-red-400',
                                    default => 'bg-zinc-800 text-zinc-400',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ $mc }}">
                                {{ $log->method ?? '—' }}
                            </span>
                        </td>

                        <td class="px-4 py-2.5 text-[#e5e7eb] max-w-[240px] truncate">
                            {{ $log->uri ?? '—' }}
                        </td>

                        <td class="px-4 py-2.5 whitespace-nowrap">
                            {{ $log->status ?? '—' }}
                        </td>

                        <td class="px-4 py-2.5 text-[#9ca3af] whitespace-nowrap">
                            {{ $log->remote_host ?? '—' }}
                        </td>

                        <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                            {{ $log->user_agent ?? '—' }}
                        </td>

                        <td class="px-4 py-2.5 text-[#6b7280] whitespace-nowrap">
                            {{ isset($log->bytes_sent) ? number_format($log->bytes_sent) : '—' }}
                        </td>

                        <td class="px-4 py-2.5 text-[#6b7280]">
                            {{ $log->referer ?? '—' }}
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-[#6b7280]">
                            No log entries found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION FĂRĂ REFRESH --}}
    @if ($logs->hasPages())
        <div class="px-4 py-3 border-t border-border bg-sidebar flex items-center justify-between">

            @if ($logs->onFirstPage())
                <span class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-[#6b7280] opacity-30">← Newer</span>
            @else
                <button wire:click="previousPage"
                    class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-label hover:text-text">
                    ← Newer
                </button>
            @endif

            <span class="text-xs font-mono text-[#6b7280]">
                {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}
            </span>

            @if ($logs->hasMorePages())
                <button wire:click="nextPage"
                    class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-label hover:text-text">
                    Older →
                </button>
            @else
                <span class="px-3 py-1.5 bg-panel border border-border rounded text-xs font-mono text-[#6b7280] opacity-30">Older →</span>
            @endif

        </div>
    @endif

</div>
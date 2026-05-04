<table class="w-full text-xs font-mono">

    <thead>
        <tr class="bg-sidebar border-b border-border">
            <th class="px-4 py-3 text-left text-muted">IP Address</th>
            <th class="px-4 py-3 text-left text-muted">Total Requests</th>
            <th class="px-4 py-3 text-left text-muted">Total Traffic</th>
            <th class="px-4 py-3 text-left text-muted">Last Seen</th>
        </tr>
    </thead>

    <tbody class="divide-y divide-border">
        @forelse ($logs as $log)
            <tr class="bg-main hover:bg-panel">

                <td class="px-4 py-2.5 font-semibold">
                    {{ $log->remote_host }}
                </td>

                <td class="px-4 py-2.5">
                    {{ number_format($log->total) }}
                </td>

                <td class="px-4 py-2.5">
                    {{ number_format($log->total_bytes) }} B
                </td>

                <td class="px-4 py-2.5">
                    {{ date('Y-m-d H:i:s', $log->last_seen) }}
                </td>

            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center py-10 text-muted">
                    No data
                </td>
            </tr>
        @endforelse
    </tbody>

</table>
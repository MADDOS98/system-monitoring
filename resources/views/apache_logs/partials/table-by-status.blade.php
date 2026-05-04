<table class="w-full text-xs font-mono">
    {{-- ── BY STATUS: 2 coloane ── --}}
    <thead>
        <tr class="bg-sidebar border-b border-border">
            <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Status</th>
            <th class="px-4 py-3 text-left text-muted uppercase tracking-widest font-semibold whitespace-nowrap">Accesses</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-border">
        @forelse ($logs as $row)
        <tr class="bg-main hover:bg-panel transition-colors duration-100">

            <td class="px-4 py-2.5 whitespace-nowrap">
                @php
                $statusColor = match(true) {
                $row->status >= 500 => 'bg-danger/10 text-danger',
                $row->status >= 400 => 'bg-warning/10 text-warning',
                $row->status >= 300 => 'bg-accent/10 text-live',
                $row->status >= 200 => 'bg-live/10 text-accent',
                default => 'bg-border text-muted',
                };
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $statusColor }}">
                    {{ $row->status }}
                </span>
            </td>

            <td class="px-4 py-2.5 text-text font-semibold whitespace-nowrap">
                {{ number_format($row->total) }}
            </td>

        </tr>
        @empty
        <tr>
            <td colspan="2" class="px-4 py-12 text-center text-muted font-mono">
                No log entries found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
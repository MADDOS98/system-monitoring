<div>
    @if($open)
    {{-- Backdrop --}}
    <div class="fixed inset-0 z-50 bg-black/60 flex items-start justify-center pt-20 px-4 overflow-y-auto"
         wire:click.self="closeModal">

        {{-- Modal panel --}}
        <div class="w-full max-w-4xl bg-[#0f0f0f] border border-neutral-800 rounded-lg shadow-2xl">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-800">
                <div>
                    <h2 class="text-sm font-mono font-semibold text-neutral-100">IP Reputations</h2>
                    <p class="text-[11px] font-mono text-neutral-500 mt-0.5">
                        manage trusted / warning / danger hosts
                    </p>
                </div>
                <button type="button"
                        wire:click="closeModal"
                        class="text-neutral-500 hover:text-neutral-200 text-xl leading-none px-2">
                    &times;
                </button>
            </div>

            {{-- Toolbar --}}
            <div class="flex items-center justify-between px-6 py-3 border-b border-neutral-800">
                <span class="text-[11px] font-mono text-neutral-500">
                    {{ $reputations->count() }} {{ $reputations->count() === 1 ? 'entry' : 'entries' }}
                </span>
                <button type="button"
                        wire:click="openAddForm"
                        class="px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150">
                    + Add
                </button>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-xs font-mono">
                    <thead class="text-[11px] uppercase tracking-widest text-neutral-500 border-b border-neutral-800">
                        <tr>
                            <th class="text-left px-6 py-2">IP</th>
                            <th class="text-left px-4 py-2">Host</th>
                            <th class="text-left px-4 py-2">Status</th>
                            <th class="text-left px-4 py-2">Reason</th>
                            <th class="text-right px-6 py-2 w-44">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-neutral-200">
                        @forelse($reputations as $rep)
                            @php
                                $statusMap = [
                                    1 => ['label' => 'trusted', 'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30', 'dot' => 'bg-emerald-400'],
                                    2 => ['label' => 'warning', 'badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/30',    'dot' => 'bg-amber-400'],
                                    3 => ['label' => 'danger',  'badge' => 'bg-red-500/10 text-red-400 border-red-500/30',          'dot' => 'bg-red-400'],
                                ][$rep->status] ?? ['label' => 'unknown', 'badge' => 'bg-neutral-700 text-neutral-300', 'dot' => 'bg-neutral-400'];
                            @endphp
                            <tr class="border-b border-neutral-800/50">
                                <td class="px-6 py-3">{{ $rep->ip }}</td>
                                <td class="px-4 py-3 text-neutral-300">{{ $rep->host }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[11px] {{ $statusMap['badge'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $statusMap['dot'] }}"></span>
                                        {{ $statusMap['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-neutral-400">{{ $rep->reason ?? '—' }}</td>
                                <td class="px-6 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button"
                                                wire:click="openEditForm({{ $rep->id }})"
                                                class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-label hover:text-text transition-colors">
                                            Edit
                                        </button>
                                        @if($confirmId === $rep->id)
                                            <button type="button"
                                                    wire:click="delete({{ $rep->id }})"
                                                    class="px-2.5 py-1 border rounded-md text-[11px] font-mono bg-red-500/20 border-red-500/40 text-red-300 hover:bg-red-500/30 transition-colors">
                                                Confirm?
                                            </button>
                                            <button type="button"
                                                    wire:click="cancelConfirm"
                                                    class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-neutral-500 hover:text-neutral-200">
                                                Cancel
                                            </button>
                                        @else
                                            <button type="button"
                                                    wire:click="delete({{ $rep->id }})"
                                                    class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-red-400 hover:text-red-300 transition-colors">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-neutral-500">No reputations defined yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Form (sub tabel) --}}
            @if($formOpen)
            <div class="border-t border-neutral-800 px-6 py-4 bg-[#0a0a0a]">
                <p class="text-[11px] font-mono uppercase tracking-widest text-neutral-500 mb-3">
                    {{ $editingId ? 'Edit entry' : 'New entry' }}
                </p>

                <form wire:submit="save" class="grid grid-cols-12 gap-3">
                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">IP</label>
                        <input type="text" wire:model="ip"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="192.168.1.10">
                        @error('ip') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Host</label>
                        <input type="text" wire:model="host"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="example.com">
                        @error('host') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Status</label>
                        <select wire:model="status"
                                class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600">
                            <option value="1">Trusted</option>
                            <option value="2">Warning</option>
                            <option value="3">Danger</option>
                        </select>
                        @error('status') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-4">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Reason <span class="text-neutral-600">(optional)</span></label>
                        <input type="text" wire:model="reason"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="known scanner, internal CI, etc.">
                        @error('reason') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-12 flex items-center justify-end gap-2 mt-1">
                        <button type="button"
                                wire:click="cancelForm"
                                class="px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-neutral-500 hover:text-neutral-200">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-3 py-1.5 bg-emerald-500/15 border border-emerald-500/40 rounded-md text-xs font-mono text-emerald-300 hover:bg-emerald-500/25">
                            {{ $editingId ? 'Update' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
            @endif

        </div>
    </div>
    @endif
</div>

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
                    <h2 class="text-sm font-mono font-semibold text-neutral-100">IP Groups</h2>
                    <p class="text-[11px] font-mono text-neutral-500 mt-0.5">
                        group IPs that represent the same host (e.g. localhost variants)
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
                    {{ $groups->count() }} {{ $groups->count() === 1 ? 'mapping' : 'mappings' }}
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
                            <th class="text-left px-4 py-2">Group name</th>
                            <th class="text-right px-6 py-2 w-44">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-neutral-200">
                        @forelse($groups as $row)
                            <tr class="border-b border-neutral-800/50">
                                <td class="px-6 py-3">{{ $row->ip }}</td>
                                <td class="px-4 py-3 text-emerald-300">{{ $row->group_name }}</td>
                                <td class="px-6 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button"
                                                wire:click="openEditForm({{ $row->id }})"
                                                class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-label hover:text-text transition-colors">
                                            Edit
                                        </button>
                                        @if($confirmId === $row->id)
                                            <button type="button"
                                                    wire:click="delete({{ $row->id }})"
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
                                                    wire:click="delete({{ $row->id }})"
                                                    class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-red-400 hover:text-red-300 transition-colors">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-neutral-500">
                                    No IP groups defined yet. Click "+ Add" to create one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Form (under table) --}}
            @if($formOpen)
            <div class="border-t border-neutral-800 px-6 py-4 bg-[#0a0a0a]">
                <p class="text-[11px] font-mono uppercase tracking-widest text-neutral-500 mb-3">
                    {{ $editingId ? 'Edit mapping' : 'New mapping' }}
                </p>

                <form wire:submit="save" class="grid grid-cols-12 gap-3">
                    <div class="col-span-5">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">IP</label>
                        <input type="text" wire:model="ip"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="127.0.0.1 or :: or 0.0.0.0">
                        @error('ip') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-5">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Group name</label>
                        <input type="text" wire:model="group_name"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="localhost">
                        @error('group_name') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2 flex items-end justify-end gap-2">
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

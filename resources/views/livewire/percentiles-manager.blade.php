<div>
    @if($open)
    {{-- Backdrop --}}
    <div class="fixed inset-0 z-50 bg-black/60 flex items-start justify-center pt-20 px-4 overflow-y-auto"
         wire:click.self="closeModal">

        {{-- Modal panel --}}
        <div class="w-full max-w-5xl bg-[#0f0f0f] border border-neutral-800 rounded-lg shadow-2xl">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-800">
                <div>
                    <h2 class="text-sm font-mono font-semibold text-neutral-100">Percentiles</h2>
                    <p class="text-[11px] font-mono text-neutral-500 mt-0.5">
                        manage percentile definitions (metric, percentile, window)
                    </p>
                </div>
                <button type="button"
                        wire:click="closeModal"
                        class="text-neutral-500 hover:text-neutral-200 text-xl leading-none px-2">
                    &times;
                </button>
            </div>

            {{-- Tab bar --}}
            <div class="flex items-center px-6 py-3 border-b border-neutral-800 gap-2">
                <button type="button"
                        wire:click="setTab('list')"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-mono transition-colors duration-150
                               {{ $tab === 'list' ? 'bg-[#1f2937] text-text border border-border' : 'text-muted hover:text-text border border-transparent' }}">
                    Percentiles
                    <span class="text-[10px] bg-neutral-700 text-neutral-300 rounded-full px-1.5 py-0.5 min-w-[18px] text-center">{{ $percentiles->count() }}</span>
                </button>
                <button type="button"
                        wire:click="setTab('form')"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-mono transition-colors duration-150
                               {{ $tab === 'form' ? 'bg-[#1f2937] text-text border border-border' : 'text-muted hover:text-text border border-transparent' }}">
                    @if($editingId)
                        Edit #{{ $editingId }}
                    @else
                        + Add new
                    @endif
                </button>
            </div>

            {{-- LIST TAB --}}
            @if($tab === 'list')
            <div class="overflow-x-auto overflow-y-auto max-h-[540px]">
                <table class="w-full text-xs font-mono">
                    <thead class="text-[11px] uppercase tracking-widest text-neutral-500 border-b border-neutral-800">
                        <tr>
                            <th class="text-left px-6 py-2">Name</th>
                            <th class="text-left px-3 py-2">Metric</th>
                            <th class="text-right px-3 py-2">Percentile</th>
                            <th class="text-right px-3 py-2">Window (min)</th>
                            <th class="text-center px-3 py-2">Active</th>
                            <th class="text-right px-6 py-2 w-44">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-neutral-200 divide-y divide-neutral-800/50">
                        @forelse($percentiles as $p)
                            <tr wire:key="percentile-row-{{ $p->id }}" class="border-b border-neutral-800/50">
                                <td class="px-6 py-3">{{ $p->name }}</td>
                                <td class="px-3 py-3 text-neutral-300">{{ $p->metric }}</td>
                                <td class="px-3 py-3 text-right">P{{ (int) $p->percentile }} <span class="text-neutral-500">({{ rtrim(rtrim(number_format($p->percentile, 2), '0'), '.') }}%)</span></td>
                                <td class="px-3 py-3 text-right text-neutral-300">{{ $p->window_minutes }}m</td>
                                <td class="px-3 py-3 text-center">
                                    <button type="button"
                                            wire:click="toggleActive({{ $p->id }})"
                                            title="{{ $p->is_active ? 'Disable' : 'Enable' }}"
                                            class="inline-flex items-center justify-center w-8 h-4 rounded-full transition-colors duration-150
                                                   {{ $p->is_active ? 'bg-emerald-500/30 border border-emerald-500/50' : 'bg-neutral-800 border border-neutral-700' }}">
                                        <span class="w-3 h-3 rounded-full transition-transform duration-150
                                                     {{ $p->is_active ? 'translate-x-1.5 bg-emerald-300' : '-translate-x-1.5 bg-neutral-500' }}"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button"
                                                wire:click="openEditForm({{ $p->id }})"
                                                class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-label hover:text-text transition-colors">
                                            Edit
                                        </button>
                                        @if($confirmId === $p->id)
                                            <button type="button"
                                                    wire:click="delete({{ $p->id }})"
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
                                                    wire:click="delete({{ $p->id }})"
                                                    class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-red-400 hover:text-red-300 transition-colors">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-neutral-500">No percentiles defined yet. Switch to "Add new" tab to create one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            {{-- FORM TAB --}}
            @if($tab === 'form')
            <div class="px-6 py-5">
                <p class="text-[11px] font-mono uppercase tracking-widest text-neutral-500 mb-4">
                    {{ $editingId ? 'Edit percentile' : 'New percentile' }}
                </p>

                <form wire:submit="save" class="grid grid-cols-12 gap-3">
                    {{-- Row 1 --}}
                    <div class="col-span-5">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Name</label>
                        <input type="text" wire:model="name"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="CPU P95">
                        @error('name') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-4">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Metric</label>
                        <select wire:model="metric"
                                class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600">
                            @foreach(\App\Models\AlertRule::METRICS as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                        @error('metric') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-3 flex items-end">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="is_active"
                                   class="w-4 h-4 rounded bg-panel border border-border text-emerald-400 focus:ring-0">
                            <span class="text-xs font-mono text-text">Active</span>
                        </label>
                    </div>

                    {{-- Row 2 --}}
                    <div class="col-span-6">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Percentile <span class="text-neutral-600">(0.01–99.99)</span></label>
                        <input type="number" min="0.01" max="99.99" step="0.01" wire:model="percentile"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="95">
                        @error('percentile') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-6">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Window (minutes) <span class="text-neutral-600">(1–10080)</span></label>
                        <input type="number" min="1" max="10080" step="1" wire:model="window_minutes"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="15">
                        @error('window_minutes') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="col-span-12 flex items-center justify-end gap-2 mt-4 pt-3 border-t border-neutral-800">
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

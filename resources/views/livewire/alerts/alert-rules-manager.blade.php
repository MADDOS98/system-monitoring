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
                    <h2 class="text-sm font-mono font-semibold text-neutral-100">Alert Rules</h2>
                    <p class="text-[11px] font-mono text-neutral-500 mt-0.5">
                        manage spike-detection conditions (metric, threshold, window, ratio)
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
                    Rules
                    <span class="text-[10px] bg-neutral-700 text-neutral-300 rounded-full px-1.5 py-0.5 min-w-[18px] text-center">{{ $rules->count() }}</span>
                </button>
                <button type="button"
                        wire:click="setTab('form')"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-mono transition-colors duration-150
                               {{ $tab === 'form' ? 'bg-[#1f2937] text-text border border-border' : 'text-muted hover:text-text border border-transparent' }}">
                    @if($editingId)
                        Edit rule #{{ $editingId }}
                    @else
                        + Add new
                    @endif
                </button>
            </div>

            {{-- ============================================================ --}}
            {{-- LIST TAB                                                     --}}
            {{-- ============================================================ --}}
            @if($tab === 'list')
            <div class="overflow-x-auto overflow-y-auto max-h-[540px]">
                <table class="w-full text-xs font-mono">
                    <thead class="text-[11px] uppercase tracking-widest text-neutral-500 border-b border-neutral-800">
                        <tr>
                            <th class="text-left px-6 py-2">Name</th>
                            <th class="text-left px-3 py-2">Metric</th>
                            <th class="text-left px-2 py-2">Op</th>
                            <th class="text-right px-3 py-2">Threshold</th>
                            <th class="text-left px-3 py-2">Level</th>
                            <th class="text-right px-3 py-2">Window</th>
                            <th class="text-right px-3 py-2">Ratio</th>
                            <th class="text-right px-3 py-2">Reset</th>
                            <th class="text-center px-3 py-2">Active</th>
                            <th class="text-right px-6 py-2 w-44">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-neutral-200 divide-y divide-neutral-800/50">
                        @forelse($rules as $rule)
                            @php
                                $levelBadge = match($rule->level) {
                                    'critical' => 'bg-red-500/10 text-red-400 border-red-500/30',
                                    'warning'  => 'bg-amber-500/10 text-amber-400 border-amber-500/30',
                                    'info'     => 'bg-blue-500/10 text-blue-400 border-blue-500/30',
                                    default    => 'bg-neutral-700 text-neutral-300',
                                };
                                $levelDot = match($rule->level) {
                                    'critical' => 'bg-red-400',
                                    'warning'  => 'bg-amber-400',
                                    'info'     => 'bg-blue-400',
                                    default    => 'bg-neutral-400',
                                };
                            @endphp
                            <tr wire:key="rule-{{ $rule->id }}" class="border-b border-neutral-800/50">
                                <td class="px-6 py-3">{{ $rule->name }}</td>
                                <td class="px-3 py-3 text-neutral-300">{{ $rule->metric }}</td>
                                <td class="px-2 py-3 text-center">{{ $rule->operator }}</td>
                                <td class="px-3 py-3 text-right">{{ $rule->threshold }}</td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md border text-[11px] {{ $levelBadge }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $levelDot }}"></span>
                                        {{ $rule->level }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right text-neutral-300">{{ $rule->window_sec }}s</td>
                                <td class="px-3 py-3 text-right text-neutral-300">{{ $rule->ratio }}</td>
                                <td class="px-3 py-3 text-right text-neutral-300">{{ $rule->inactive_reset_sec }}s</td>
                                <td class="px-3 py-3 text-center">
                                    <button type="button"
                                            wire:click="toggleActive({{ $rule->id }})"
                                            title="{{ $rule->is_active ? 'Disable rule' : 'Enable rule' }}"
                                            class="inline-flex items-center justify-center w-8 h-4 rounded-full transition-colors duration-150
                                                   {{ $rule->is_active ? 'bg-emerald-500/30 border border-emerald-500/50' : 'bg-neutral-800 border border-neutral-700' }}">
                                        <span class="w-3 h-3 rounded-full transition-transform duration-150
                                                     {{ $rule->is_active ? 'translate-x-1.5 bg-emerald-300' : '-translate-x-1.5 bg-neutral-500' }}"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button"
                                                wire:click="openEditForm({{ $rule->id }})"
                                                class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-label hover:text-text transition-colors">
                                            Edit
                                        </button>
                                        @if($confirmId === $rule->id)
                                            <button type="button"
                                                    wire:click="delete({{ $rule->id }})"
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
                                                    wire:click="delete({{ $rule->id }})"
                                                    class="px-2.5 py-1 bg-panel border border-border rounded-md text-[11px] font-mono text-red-400 hover:text-red-300 transition-colors">
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-neutral-500">No rules defined yet. Switch to "Add new" tab to create one.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @endif

            {{-- ============================================================ --}}
            {{-- FORM TAB                                                     --}}
            {{-- ============================================================ --}}
            @if($tab === 'form')
            <div class="px-6 py-5">
                <p class="text-[11px] font-mono uppercase tracking-widest text-neutral-500 mb-4">
                    {{ $editingId ? 'Edit rule' : 'New rule' }}
                </p>

                <form wire:submit="save" class="grid grid-cols-12 gap-3">
                    {{-- Row 1 --}}
                    <div class="col-span-4">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Name</label>
                        <input type="text" wire:model="name"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="CPU warning">
                        @error('name') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Metric</label>
                        <select wire:model="metric"
                                class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600">
                            @foreach(\App\Models\AlertRule::METRICS as $m)
                                <option value="{{ $m }}">{{ $m }}</option>
                            @endforeach
                        </select>
                        @error('metric') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-2">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Operator</label>
                        <select wire:model="operator"
                                class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600">
                            @foreach(\App\Models\AlertRule::OPERATORS as $op)
                                <option value="{{ $op }}">{{ $op }}</option>
                            @endforeach
                        </select>
                        @error('operator') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Threshold</label>
                        <input type="number" step="0.01" wire:model="threshold"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="75">
                        @error('threshold') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Row 2 --}}
                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Level</label>
                        <select wire:model="level"
                                class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600">
                            @foreach(\App\Models\AlertRule::LEVELS as $lvl)
                                <option value="{{ $lvl }}">{{ $lvl }}</option>
                            @endforeach
                        </select>
                        @error('level') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Window (sec)</label>
                        <input type="number" min="1" step="1" wire:model="window_sec"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="60">
                        @error('window_sec') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Inactive reset (sec) <span class="text-neutral-600">(&lt; window)</span></label>
                        <input type="number" min="1" max="{{ max(1, $window_sec - 1) }}" step="1" wire:model="inactive_reset_sec"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="15">
                        @error('inactive_reset_sec') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="col-span-3">
                        <label class="block text-[11px] font-mono text-neutral-500 mb-1">Ratio <span class="text-neutral-600">(0.01–1.00)</span></label>
                        <input type="number" min="0.01" max="1" step="0.01" wire:model="ratio"
                               class="w-full px-3 py-1.5 bg-panel border border-border rounded-md text-xs text-text font-mono outline-none focus:border-neutral-600"
                               placeholder="0.6">
                        @error('ratio') <p class="text-[11px] text-red-400 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Row 3 (active toggle) --}}
                    <div class="col-span-12 flex items-center">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="is_active"
                                   class="w-4 h-4 rounded bg-panel border border-border text-emerald-400 focus:ring-0">
                            <span class="text-xs font-mono text-text">Active</span>
                        </label>
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
                            {{ $editingId ? 'Update rule' : 'Add rule' }}
                        </button>
                    </div>
                </form>
            </div>
            @endif

        </div>
    </div>
    @endif
</div>

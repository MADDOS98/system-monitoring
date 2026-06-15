@php
    // Format minutes → human readable (years > months > days > hours > min).
    $fmtMinutes = function (int $min): string {
        if ($min >= 525600) return number_format($min / 525600, 1) . ' years';
        if ($min >= 43200)  return number_format($min / 43200, 1)  . ' months';
        if ($min >= 1440)   return number_format($min / 1440, 1)   . ' days';
        if ($min >= 60)     return number_format($min / 60, 1)     . ' hours';
        return $min . ' min';
    };
@endphp

<div class="space-y-4 max-w-3xl">

    {{-- Retention Settings panel ────────────────────────────────────────── --}}
    <div class="border border-border rounded-lg p-6">
        <section>

            <header class="mb-5">
                <h2 class="text-sm font-semibold text-text font-mono uppercase tracking-widest">
                    Data Retention
                </h2>
                <p class="mt-2 text-sm text-muted font-mono">
                    Configure how many minutes of historical data to keep in the database for each category.
                    Older records become eligible for pruning so the database doesn't grow infinitely.
                </p>
            </header>

            {{-- Table ────────────────────────────────────────────────────── --}}
            <div class="border border-border rounded-md overflow-hidden">

                {{-- Column headers --}}
                <div class="grid grid-cols-12 bg-sidebar border-b border-border px-4 py-2.5 text-[10px] font-mono font-semibold text-muted uppercase tracking-widest">
                    <div class="col-span-4">Constant</div>
                    <div class="col-span-3">Minutes</div>
                    <div class="col-span-3">Duration</div>
                    <div class="col-span-2 text-right">Actions</div>
                </div>

                {{-- Rows --}}
                @forelse($settings as $row)
                    @if($editingConstant === $row->constant)
                        {{-- ────── Edit mode ────── --}}
                        <div wire:key="edit-{{ $row->constant }}"
                             class="grid grid-cols-12 px-4 py-2.5 items-start bg-[#161616] border-b border-border last:border-b-0">

                            <div class="col-span-4 font-mono text-sm text-text pt-2">
                                {{ $row->constant }}
                            </div>

                            <div class="col-span-3 pr-3">
                                <x-text-input wire:model.live="editingMinutes"
                                              type="number"
                                              min="1"
                                              class="w-full text-sm py-1.5" />
                                <x-input-error class="mt-1" :messages="$errors->get('editingMinutes')" />
                            </div>

                            <div class="col-span-3 font-mono text-xs text-label pt-2">
                                {{ $fmtMinutes(max(0, (int) $editingMinutes)) }}
                            </div>

                            <div class="col-span-2 flex items-center justify-end gap-3 pt-2">
                                <button type="button"
                                        wire:click="saveEdit"
                                        class="text-xs font-mono text-emerald-400 hover:text-emerald-300 transition-colors cursor-pointer">
                                    Save
                                </button>
                                <button type="button"
                                        wire:click="cancelEdit"
                                        class="text-xs font-mono text-muted hover:text-text transition-colors cursor-pointer">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- ────── View mode ────── --}}
                        <div wire:key="row-{{ $row->constant }}"
                             class="grid grid-cols-12 px-4 py-2.5 items-center bg-[#111111] hover:bg-[#161616] transition-colors duration-100 border-b border-border last:border-b-0">

                            <div class="col-span-4 font-mono text-sm font-semibold text-text">
                                {{ $row->constant }}
                            </div>

                            <div class="col-span-3 font-mono text-sm text-text">
                                {{ number_format($row->minutes) }}
                            </div>

                            <div class="col-span-3 font-mono text-xs text-label">
                                {{ $fmtMinutes((int) $row->minutes) }}
                            </div>

                            <div class="col-span-2 flex items-center justify-end gap-3">
                                <button type="button"
                                        wire:click="startEdit('{{ $row->constant }}')"
                                        class="text-xs font-mono text-label hover:text-text transition-colors cursor-pointer">
                                    Edit
                                </button>
                                <button type="button"
                                        wire:click="delete('{{ $row->constant }}')"
                                        wire:confirm="Delete retention setting '{{ $row->constant }}'? This action cannot be undone."
                                        class="text-xs font-mono text-red-400 hover:text-red-300 transition-colors cursor-pointer">
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="px-4 py-8 text-center text-sm font-mono text-muted bg-[#111111]">
                        No retention settings configured.
                    </div>
                @endforelse

                {{-- Add new row inline form --}}
                @if($showAddForm)
                    <div wire:key="add-form"
                         class="grid grid-cols-12 px-4 py-2.5 items-start bg-[#0f1a14] border-b border-border last:border-b-0">

                        <div class="col-span-4 pr-3">
                            <x-text-input wire:model.live="newConstant"
                                          type="text"
                                          placeholder="NEW_CATEGORY"
                                          class="w-full text-sm py-1.5 uppercase" />
                            <x-input-error class="mt-1" :messages="$errors->get('newConstant')" />
                        </div>

                        <div class="col-span-3 pr-3">
                            <x-text-input wire:model.live="newMinutes"
                                          type="number"
                                          min="1"
                                          class="w-full text-sm py-1.5" />
                            <x-input-error class="mt-1" :messages="$errors->get('newMinutes')" />
                        </div>

                        <div class="col-span-3 font-mono text-xs text-label pt-2">
                            {{ $fmtMinutes(max(0, (int) $newMinutes)) }}
                        </div>

                        <div class="col-span-2 flex items-center justify-end gap-3 pt-2">
                            <button type="button"
                                    wire:click="saveAdd"
                                    class="text-xs font-mono text-emerald-400 hover:text-emerald-300 transition-colors cursor-pointer">
                                Save
                            </button>
                            <button type="button"
                                    wire:click="cancelAdd"
                                    class="text-xs font-mono text-muted hover:text-text transition-colors cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </div>
                @endif

            </div>

            {{-- Add new button (afisat doar cand nu e add form deschis) --}}
            @if(! $showAddForm)
                <div class="mt-4 flex justify-end">
                    <button type="button"
                            wire:click="startAdd"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-panel border border-border rounded-md text-xs font-mono text-label hover:text-text transition-colors duration-150 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Add new
                    </button>
                </div>
            @endif

        </section>
    </div>

</div>

<?php

namespace App\Livewire;

use App\Models\RetentionSetting;
use Livewire\Component;

class SettingsPage extends Component
{
    // Edit-in-place state
    public ?string $editingConstant = null;
    public int     $editingMinutes  = 0;

    // Add new row state
    public bool   $showAddForm = false;
    public string $newConstant = '';
    public int    $newMinutes  = 1440; // 1 zi default

    // ───────────────────── Edit ─────────────────────

    public function startEdit(string $constant): void
    {
        $row = RetentionSetting::where('constant', $constant)->first();
        if (! $row) {
            return;
        }
        $this->editingConstant = $constant;
        $this->editingMinutes  = (int) $row->minutes;
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->editingConstant = null;
        $this->editingMinutes  = 0;
        $this->resetErrorBag();
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editingMinutes' => 'required|integer|min:1',
        ], [
            'editingMinutes.min' => 'Minutes must be at least 1.',
        ]);

        RetentionSetting::where('constant', $this->editingConstant)
            ->update(['minutes' => $this->editingMinutes]);

        $this->cancelEdit();
    }

    // ───────────────────── Delete ─────────────────────

    public function delete(string $constant): void
    {
        RetentionSetting::where('constant', $constant)->delete();
    }

    // ───────────────────── Add new ─────────────────────

    public function startAdd(): void
    {
        $this->showAddForm = true;
        $this->newConstant = '';
        $this->newMinutes  = 1440;
        $this->resetErrorBag();
    }

    public function cancelAdd(): void
    {
        $this->showAddForm = false;
        $this->newConstant = '';
        $this->newMinutes  = 1440;
        $this->resetErrorBag();
    }

    public function saveAdd(): void
    {
        $this->validate([
            'newConstant' => 'required|string|max:50|regex:/^[A-Z][A-Z0-9_]*$/',
            'newMinutes'  => 'required|integer|min:1',
        ], [
            'newConstant.regex' => 'Use uppercase letters, digits and underscores only (e.g. METRICS, APACHE_LOGS).',
            'newMinutes.min'    => 'Minutes must be at least 1.',
        ]);

        // Uniqueness check pe conexiunea system_metrics (Laravel's unique rule
        // foloseste conexiunea default, deci verificam manual).
        if (RetentionSetting::where('constant', $this->newConstant)->exists()) {
            $this->addError('newConstant', 'A setting with this constant already exists.');
            return;
        }

        RetentionSetting::create([
            'constant' => $this->newConstant,
            'minutes'  => $this->newMinutes,
        ]);

        $this->cancelAdd();
    }

    public function render()
    {
        $settings = RetentionSetting::orderBy('constant')->get();

        return view('livewire.settings-page', [
            'settings' => $settings,
        ]);
    }
}

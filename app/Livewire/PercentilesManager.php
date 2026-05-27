<?php

namespace App\Livewire;

use App\Models\AlertRule;
use App\Models\Percentile;
use Livewire\Attributes\On;
use Livewire\Component;

class PercentilesManager extends Component
{
    public bool $open      = false;
    public string $tab     = 'list';
    public ?int $editingId = null;
    public ?int $confirmId = null;

    public string $name           = '';
    public string $metric         = 'cpu';
    public float  $percentile     = 95.0;
    public int    $window_minutes = 15;
    public bool   $is_active      = true;

    protected function rules(): array
    {
        return [
            'name'           => 'required|string|max:100',
            'metric'         => 'required|string|in:' . implode(',', AlertRule::METRICS),
            'percentile'     => 'required|numeric|min:0.01|max:99.99',
            'window_minutes' => 'required|integer|min:1|max:10080',  // max ~1 saptamana
            'is_active'      => 'boolean',
        ];
    }

    #[On('open-percentiles-manager')]
    public function openModal(): void
    {
        $this->open = true;
        $this->tab  = 'list';
        $this->resetFormState();
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetFormState();
    }

    public function setTab(string $tab): void
    {
        if (!in_array($tab, ['list', 'form'], true)) {
            return;
        }
        $this->tab       = $tab;
        $this->confirmId = null;

        if ($tab === 'form' && $this->editingId === null) {
            $this->resetFormFields();
        }
    }

    public function openEditForm(int $id): void
    {
        $p = Percentile::find($id);
        if (!$p) {
            return;
        }
        $this->editingId      = $id;
        $this->name           = $p->name;
        $this->metric         = $p->metric;
        $this->percentile     = (float) $p->percentile;
        $this->window_minutes = (int) $p->window_minutes;
        $this->is_active      = (bool) $p->is_active;
        $this->tab            = 'form';
        $this->confirmId      = null;
        $this->resetErrorBag();
    }

    public function cancelForm(): void
    {
        $this->resetFormState();
        $this->tab = 'list';
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            Percentile::find($this->editingId)?->update($data);
        } else {
            Percentile::create($data);
        }

        $this->resetFormState();
        $this->tab = 'list';
    }

    public function delete(int $id): void
    {
        if ($this->confirmId !== $id) {
            $this->confirmId = $id;
            return;
        }

        Percentile::find($id)?->delete();
        $this->confirmId = null;
    }

    public function cancelConfirm(): void
    {
        $this->confirmId = null;
    }

    public function toggleActive(int $id): void
    {
        $p = Percentile::find($id);
        if (!$p) {
            return;
        }
        $p->is_active = !$p->is_active;
        $p->save();
    }

    private function resetFormFields(): void
    {
        $this->editingId      = null;
        $this->name           = '';
        $this->metric         = 'cpu';
        $this->percentile     = 95.0;
        $this->window_minutes = 15;
        $this->is_active      = true;
        $this->resetErrorBag();
    }

    private function resetFormState(): void
    {
        $this->resetFormFields();
        $this->confirmId = null;
    }

    public function render()
    {
        return view('livewire.percentiles-manager', [
            'percentiles' => $this->open
                ? Percentile::orderBy('metric')->orderBy('percentile')->get()
                : collect(),
        ]);
    }
}

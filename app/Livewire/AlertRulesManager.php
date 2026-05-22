<?php

namespace App\Livewire;

use App\Models\AlertRule;
use Livewire\Attributes\On;
use Livewire\Component;

class AlertRulesManager extends Component
{
    public bool $open      = false;
    public string $tab     = 'list';
    public ?int $editingId = null;
    public ?int $confirmId = null;

    public string $name       = '';
    public string $metric     = 'cpu';
    public string $operator   = '>';
    public float  $threshold  = 0.0;
    public string $level      = 'warning';
    public int    $window_sec = 60;
    public float  $ratio      = 0.6;
    public bool   $is_active  = true;

    protected function rules(): array
    {
        return [
            'name'       => 'required|string|max:100',
            'metric'     => 'required|string|in:' . implode(',', AlertRule::METRICS),
            'operator'   => 'required|string|in:' . implode(',', AlertRule::OPERATORS),
            'threshold'  => 'required|numeric',
            'level'      => 'required|string|in:' . implode(',', AlertRule::LEVELS),
            'window_sec' => 'required|integer|min:1',
            'ratio'      => 'required|numeric|min:0.01|max:1',
            'is_active'  => 'boolean',
        ];
    }

    #[On('open-alert-rules-manager')]
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
        $rule = AlertRule::find($id);
        if (!$rule) {
            return;
        }
        $this->editingId  = $id;
        $this->name       = $rule->name;
        $this->metric     = $rule->metric;
        $this->operator   = $rule->operator;
        $this->threshold  = (float) $rule->threshold;
        $this->level      = $rule->level;
        $this->window_sec = (int) $rule->window_sec;
        $this->ratio      = (float) $rule->ratio;
        $this->is_active  = (bool) $rule->is_active;
        $this->tab        = 'form';
        $this->confirmId  = null;
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
            AlertRule::find($this->editingId)?->update($data);
        } else {
            AlertRule::create($data);
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

        AlertRule::find($id)?->delete();
        $this->confirmId = null;
    }

    public function cancelConfirm(): void
    {
        $this->confirmId = null;
    }

    public function toggleActive(int $id): void
    {
        $rule = AlertRule::find($id);
        if (!$rule) {
            return;
        }
        $rule->is_active = !$rule->is_active;
        $rule->save();
    }

    private function resetFormFields(): void
    {
        $this->editingId  = null;
        $this->name       = '';
        $this->metric     = 'cpu';
        $this->operator   = '>';
        $this->threshold  = 0.0;
        $this->level      = 'warning';
        $this->window_sec = 60;
        $this->ratio      = 0.6;
        $this->is_active  = true;
        $this->resetErrorBag();
    }

    private function resetFormState(): void
    {
        $this->resetFormFields();
        $this->confirmId = null;
    }

    public function render()
    {
        return view('livewire.alert-rules-manager', [
            'rules' => $this->open
                ? AlertRule::orderBy('metric')
                    ->orderByRaw("CASE level WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
                    ->get()
                : collect(),
        ]);
    }
}

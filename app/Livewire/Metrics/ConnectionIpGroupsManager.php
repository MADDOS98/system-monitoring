<?php

namespace App\Livewire\Metrics;

use App\Models\ConnectionIpGroup;
use Livewire\Attributes\On;
use Livewire\Component;

class ConnectionIpGroupsManager extends Component
{
    public bool $open      = false;
    public bool $formOpen  = false;
    public ?int $editingId = null;
    public ?int $confirmId = null;

    // Form state
    public string $ip         = '';
    public string $group_name = '';

    protected function rules(): array
    {
        return [
            'ip'         => 'required|string|max:64',
            'group_name' => 'required|string|max:64',
        ];
    }

    #[On('open-connection-ip-groups')]
    public function openModal(): void
    {
        $this->open = true;
        $this->resetState();
    }

    public function closeModal(): void
    {
        $this->open = false;
        $this->resetState();
    }

    public function openAddForm(): void
    {
        $this->resetForm();
        $this->formOpen  = true;
        $this->confirmId = null;
    }

    public function openEditForm(int $id): void
    {
        $row = ConnectionIpGroup::find($id);
        if (! $row) {
            return;
        }
        $this->editingId  = $id;
        $this->ip         = $row->ip;
        $this->group_name = $row->group_name;
        $this->formOpen   = true;
        $this->confirmId  = null;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->formOpen = false;
    }

    public function save(): void
    {
        $data = $this->validate();

        // Uniqueness check manual: tabela e pe system_metrics, nu pe default.
        $conflict = ConnectionIpGroup::where('ip', $this->ip)
            ->when($this->editingId, fn ($q) => $q->where('id', '!=', $this->editingId))
            ->exists();

        if ($conflict) {
            $this->addError('ip', 'This IP is already mapped to a group.');
            return;
        }

        if ($this->editingId) {
            ConnectionIpGroup::find($this->editingId)?->update($data);
        } else {
            ConnectionIpGroup::create($data);
        }

        $this->resetForm();
        $this->formOpen = false;
    }

    public function delete(int $id): void
    {
        // double-click pattern: prima apasare seteaza confirmId, a doua sterge
        if ($this->confirmId !== $id) {
            $this->confirmId = $id;
            return;
        }

        ConnectionIpGroup::find($id)?->delete();
        $this->confirmId = null;
    }

    public function cancelConfirm(): void
    {
        $this->confirmId = null;
    }

    private function resetForm(): void
    {
        $this->editingId  = null;
        $this->ip         = '';
        $this->group_name = '';
        $this->resetErrorBag();
    }

    private function resetState(): void
    {
        $this->resetForm();
        $this->formOpen  = false;
        $this->confirmId = null;
    }

    public function render()
    {
        return view('livewire.metrics.connection-ip-groups-manager', [
            'groups' => $this->open
                ? ConnectionIpGroup::orderBy('group_name')->orderBy('ip')->get()
                : collect(),
        ]);
    }
}

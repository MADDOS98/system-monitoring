<?php

namespace App\Livewire\ApacheLogs;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\HostReputation;

class HostReputations extends Component
{
    public bool $open       = false;
    public bool $formOpen   = false;
    public ?int $editingId  = null;
    public ?int $confirmId  = null; // pentru double-click pe Delete

    // Form state
    public string  $ip     = '';
    public string  $host   = '';
    public int     $status = HostReputation::STATUS_TRUSTED;
    public ?string $reason = null;

    protected function rules(): array
    {
        return [
            'ip'     => 'required|string|max:64',
            'host'   => 'required|string|max:255',
            'status' => 'required|integer|in:1,2,3',
            'reason' => 'nullable|string|max:500',
        ];
    }

    #[On('open-host-reputations')]
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
        $this->formOpen = true;
        $this->confirmId = null;
    }

    public function openEditForm(int $id): void
    {
        $rep = HostReputation::find($id);
        if (!$rep) {
            return;
        }
        $this->editingId = $id;
        $this->ip        = $rep->ip;
        $this->host      = $rep->host;
        $this->status    = $rep->status;
        $this->reason    = $rep->reason;
        $this->formOpen  = true;
        $this->confirmId = null;
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->formOpen = false;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            HostReputation::find($this->editingId)?->update($data);
        } else {
            HostReputation::create($data);
        }

        $this->resetForm();
        $this->formOpen = false;
    }

    public function delete(int $id): void
    {
        // double-click: primul click seteaza confirmId, al doilea sterge
        if ($this->confirmId !== $id) {
            $this->confirmId = $id;
            return;
        }

        HostReputation::find($id)?->delete();
        $this->confirmId = null;
    }

    public function cancelConfirm(): void
    {
        $this->confirmId = null;
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->ip        = '';
        $this->host      = '';
        $this->status    = HostReputation::STATUS_TRUSTED;
        $this->reason    = null;
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
        return view('livewire.apache-logs.host-reputations', [
            'reputations' => $this->open
                ? HostReputation::orderBy('status', 'desc')->orderBy('ip')->get()
                : collect(),
        ]);
    }
}

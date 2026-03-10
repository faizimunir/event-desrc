<?php

namespace App\Livewire\MasterOfCeremonies;

use App\Models\MasterOfCeremony;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class MasterOfCeremonyList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('mc.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function masterOfCeremonies()
    {
        return MasterOfCeremony::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.master-of-ceremonies.master-of-ceremony-list');
    }
}

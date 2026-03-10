<?php

namespace App\Livewire\RacingCommittees;

use App\Models\RacingCommittee;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class RacingCommitteeList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('rc.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function racingCommittees()
    {
        return RacingCommittee::query()
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
        return view('livewire.racing-committees.racing-committee-list');
    }
}

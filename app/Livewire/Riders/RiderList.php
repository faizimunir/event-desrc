<?php

namespace App\Livewire\Riders;

use App\Models\Rider;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class RiderList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('rider.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function riders()
    {
        return Rider::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('nickname', 'like', '%'.$this->search.'%')
                        ->orWhere('number_plate', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.riders.rider-list');
    }
}

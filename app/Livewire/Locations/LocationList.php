<?php

namespace App\Livewire\Locations;

use App\Models\Location;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LocationList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('location.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function locations()
    {
        return Location::query()
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
        return view('livewire.locations.location-list');
    }
}

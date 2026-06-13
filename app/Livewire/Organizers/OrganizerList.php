<?php

namespace App\Livewire\Organizers;

use App\Models\Organizer;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class OrganizerList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('organizer.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function organizers()
    {
        $user = auth()->user();
        $query = Organizer::query();

        if (! $user->hasRole('super_admin') && ! $user->hasRole('admin') && ! $user->hasRole('committee')) {
            $query->where('user_id', $user->id);
        }

        return $query
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
        return view('livewire.organizers.organizer-list');
    }
}

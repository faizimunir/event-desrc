<?php

namespace App\Livewire\Levels;

use App\Models\Level;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class LevelList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('level.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function levels()
    {
        return Level::query()
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('code', 'like', '%'.$this->search.'%')
                        ->orWhere('name', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('order')
            ->orderBy('id')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.levels.level-list');
    }
}

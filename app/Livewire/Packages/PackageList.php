<?php

namespace App\Livewire\Packages;

use App\Models\Event;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PackageList extends Component
{
    use WithPagination;

    public Event $event;

    public string $search = '';

    public function mount(Event $event): void
    {
        abort_unless(auth()->user()->canAs('package.read'), 403);
        $this->event = $event;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function packages()
    {
        return $this->event->packages()
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.packages.package-list');
    }
}

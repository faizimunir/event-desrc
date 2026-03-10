<?php

namespace App\Livewire\Permissions;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;

class PermissionList extends Component
{
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('super_admin'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function permissions()
    {
        return Permission::where('guard_name', 'web')
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
            ->withCount('roles')
            ->orderBy('name')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.permissions.permission-list');
    }
}

<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAs('user.read'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function roles()
    {
        return Role::where('guard_name', 'web')->orderBy('name')->get();
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->roleFilter !== '', fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter)))
            ->latest()
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.users.user-list');
    }
}

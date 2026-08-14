<?php

namespace App\Livewire\Users;

use App\Concerns\ShowsToast;
use App\Models\User;
use App\Services\UserMergeService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UserList extends Component
{
    use ShowsToast;
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    /** @var list<int|string> */
    public array $selectedUserIds = [];

    public bool $mergeModalOpen = false;

    public ?int $mergePrimaryUserId = null;

    /**
     * @var list<array{
     *     id: int,
     *     name: string,
     *     email: ?string,
     *     whatsapp: ?string,
     *     riders_display: string
     * }>
     */
    public array $mergeCandidates = [];

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

    public function setRoleFilter(string $role = ''): void
    {
        $this->roleFilter = $role;
        $this->resetPage();
    }

    public function closeMergeModal(): void
    {
        $this->mergeModalOpen = false;
    }

    public function openMergeModal(): void
    {
        abort_unless(auth()->user()->canAs('user.update') && auth()->user()->canAs('user.delete'), 403);

        $ids = array_values(array_unique(array_map('intval', $this->selectedUserIds)));

        if (count($ids) < 2) {
            $this->toast(__('Select at least two users to merge.'), 'danger');

            return;
        }

        $this->mergeCandidates = User::query()
            ->whereIn('id', $ids)
            ->with(['riders' => fn ($q) => $q->select('id', 'user_id', 'name', 'nickname')->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                $riderLabels = $u->riders
                    ->map(fn ($r) => $r->nickname ? $r->name.' ('.$r->nickname.')' : $r->name)
                    ->implode(', ');

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'whatsapp' => $u->whatsapp,
                    'riders_display' => $riderLabels !== '' ? $riderLabels : __('None'),
                ];
            })
            ->values()
            ->all();

        $this->mergePrimaryUserId = min($ids);
        $this->resetErrorBag('merge');
        $this->mergeModalOpen = true;
    }

    public function confirmMerge(UserMergeService $userMergeService): void
    {
        abort_unless(auth()->user()->canAs('user.update') && auth()->user()->canAs('user.delete'), 403);

        $ids = array_values(array_unique(array_map('intval', $this->selectedUserIds)));
        $primary = $this->mergePrimaryUserId !== null ? (int) $this->mergePrimaryUserId : null;

        if (count($ids) < 2 || $primary === null || ! in_array($primary, $ids, true)) {
            $this->toast(__('Invalid merge selection.'), 'danger');

            return;
        }

        $actor = auth()->user();

        foreach ($ids as $id) {
            $user = User::query()->find($id);
            if (! $user) {
                $this->toast(__('One or more users no longer exist.'), 'danger');

                return;
            }
            if (! Gate::forUser($actor)->allows('update', $user)) {
                $this->toast(__('You are not allowed to update one of the selected users.'), 'danger');

                return;
            }
            if ($id !== $primary && ! Gate::forUser($actor)->allows('delete', $user)) {
                $this->toast(__('You are not allowed to remove one of the selected accounts as part of this merge.'), 'danger');

                return;
            }
        }

        try {
            $userMergeService->mergeIntoPrimary($ids, $primary);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $this->toast($e->getMessage(), 'danger');

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->toast(__('Merge failed. Please try again.'), 'danger');

            return;
        }

        $this->selectedUserIds = [];
        $this->mergeModalOpen = false;
        $this->mergeCandidates = [];
        $this->mergePrimaryUserId = null;
        $this->resetPage();

        $this->toast(__('Users merged successfully.'));
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
                        ->orWhere('whatsapp', 'like', '%'.$this->search.'%');
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

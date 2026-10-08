<?php

namespace App\Livewire\Organizers;

use App\Models\Organizer;
use App\Models\User;
use Livewire\Component;

class OrganizerForm extends Component
{
    public ?Organizer $organizer = null;

    /** @var \Illuminate\Database\Eloquent\Collection<int, User> */
    public $users;

    public string $name = '';

    public string $link = '';

    /** @var list<string> ID user pengelola organizer */
    public array $user_ids = [];

    /** Apakah daftar pengelola (user_ids) boleh diedit (super_admin/admin). */
    public bool $canAssignUser = false;

    public function mount(?Organizer $organizer = null): void
    {
        $user = auth()->user();
        $this->canAssignUser = $user->hasRole('super_admin') || $user->hasRole('admin');

        if ($organizer?->exists) {
            $this->organizer = $organizer;
            $this->name = $organizer->name;
            $this->link = $organizer->link ?? '';
            $this->user_ids = $organizer->users()->pluck('users.id')->map(fn ($id) => (string) $id)->all();
        }

        if ($this->canAssignUser) {
            // User ber-role organizer, ditambah pengelola yang sudah terpasang (walau role-nya sudah dicabut).
            $this->users = User::query()
                ->role('organizer')
                ->when($this->user_ids !== [], fn ($q) => $q->orWhereIn('users.id', $this->user_ids))
                ->orderBy('name')
                ->get();
        } else {
            $this->users = collect();
        }
    }

    public function save(): void
    {
        if ($this->organizer) {
            abort_unless(auth()->user()->canAs('organizer.update'), 403);
            $this->authorize('update', $this->organizer);
        } else {
            abort_unless(auth()->user()->canAs('organizer.create'), 403);
        }

        $user = auth()->user();
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ];
        $validated = $this->validate($rules);

        $userIds = collect($validated['user_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values()->all();
        unset($validated['user_ids']);

        if ($this->organizer) {
            $this->organizer->update($validated);
            // Non-admin tidak boleh mengubah daftar pengelola.
            if ($this->canAssignUser) {
                $this->organizer->users()->sync($userIds);
            }
            session()->flash('status', __('Organizer updated.'));
            $this->redirect(route('organizers.index'), navigate: true);
        } else {
            $organizer = Organizer::create($validated);
            $organizer->users()->sync($this->canAssignUser ? $userIds : [$user->id]);
            session()->flash('status', __('Organizer created.'));
            $this->redirect(route('organizers.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.organizers.organizer-form');
    }
}

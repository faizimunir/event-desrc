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

    public ?string $user_id = null;

    /** Apakah field user (user_id) boleh diedit (super_admin/admin). */
    public bool $canAssignUser = false;

    public function mount(?Organizer $organizer = null): void
    {
        $user = auth()->user();
        $this->canAssignUser = $user->hasRole('super_admin') || $user->hasRole('admin');
        $this->users = $this->canAssignUser ? User::role('organizer')->orderBy('name')->get() : collect();

        if ($organizer?->exists) {
            $this->organizer = $organizer;
            $this->name = $organizer->name;
            $this->link = $organizer->link ?? '';
            $this->user_id = $organizer->user_id ? (string) $organizer->user_id : null;
        } else {
            if (! $this->canAssignUser) {
                $this->user_id = (string) $user->id;
            }
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
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
        $validated = $this->validate($rules);

        if (! $this->canAssignUser) {
            $validated['user_id'] = $user->id;
        } else {
            $validated['user_id'] = isset($validated['user_id']) && $validated['user_id'] !== '' ? (int) $validated['user_id'] : null;
        }

        if ($this->organizer) {
            $this->organizer->update($validated);
            session()->flash('status', __('Organizer updated.'));
            $this->redirect(route('organizers.index'), navigate: true);
        } else {
            Organizer::create($validated);
            session()->flash('status', __('Organizer created.'));
            $this->redirect(route('organizers.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.organizers.organizer-form');
    }
}

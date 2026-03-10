<?php

namespace App\Livewire\RacingCommittees;

use App\Models\RacingCommittee;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class RacingCommitteeForm extends Component
{
    use WithFileUploads;

    public ?RacingCommittee $racingCommittee = null;

    public string $name = '';

    public string $link = '';

    public $photoRc = null;

    public bool $removeExistingPhoto = false;

    public function mount(?RacingCommittee $racingCommittee = null): void
    {
        if ($racingCommittee?->exists) {
            $this->authorize('update', $racingCommittee);
            $this->racingCommittee = $racingCommittee;
            $this->name = $racingCommittee->name;
            $this->link = $racingCommittee->link ?? '';
        } else {
            abort_unless(auth()->user()->canAs('rc.create'), 403);
        }
    }

    public function removePhotoRc(): void
    {
        $this->photoRc = null;
    }

    public function removeExistingPhotoRc(): void
    {
        $this->removeExistingPhoto = true;
    }

    public function save(): void
    {
        if ($this->racingCommittee) {
            abort_unless(auth()->user()->canAs('rc.update'), 403);
            $this->authorize('update', $this->racingCommittee);
        } else {
            abort_unless(auth()->user()->canAs('rc.create'), 403);
        }

        $maxKb = config('media.max_upload_size_kb', 2048);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
            'photoRc' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ]);

        $path = $this->racingCommittee?->photo_rc;

        if ($this->removeExistingPhoto && $this->racingCommittee?->photo_rc) {
            Storage::disk('public')->delete($this->racingCommittee->photo_rc);
            $path = null;
        } elseif ($this->photoRc) {
            if ($this->racingCommittee?->photo_rc) {
                Storage::disk('public')->delete($this->racingCommittee->photo_rc);
            }
            $path = $this->photoRc->store('racing-committees', 'public');
        }

        if ($this->racingCommittee) {
            $this->racingCommittee->update([
                'name' => $this->name,
                'link' => $this->link ?: null,
                'photo_rc' => $path,
            ]);
        } else {
            RacingCommittee::create([
                'name' => $this->name,
                'link' => $this->link ?: null,
                'photo_rc' => $path,
            ]);
        }

        $this->redirect(route('racing-committees.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.racing-committees.racing-committee-form');
    }
}

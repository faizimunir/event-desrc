<?php

namespace App\Livewire\MasterOfCeremonies;

use App\Models\MasterOfCeremony;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MasterOfCeremonyForm extends Component
{
    use WithFileUploads;

    public ?MasterOfCeremony $masterOfCeremony = null;

    public string $name = '';

    public string $link = '';

    public $avatarMc = null;

    public bool $removeExistingAvatar = false;

    public function mount(?MasterOfCeremony $masterOfCeremony = null): void
    {
        if ($masterOfCeremony?->exists) {
            $this->authorize('update', $masterOfCeremony);
            $this->masterOfCeremony = $masterOfCeremony;
            $this->name = $masterOfCeremony->name;
            $this->link = $masterOfCeremony->link ?? '';
        } else {
            abort_unless(auth()->user()->canAs('mc.create'), 403);
        }
    }

    public function removeAvatarMc(): void
    {
        $this->avatarMc = null;
    }

    public function removeExistingAvatarMc(): void
    {
        $this->removeExistingAvatar = true;
    }

    public function save(): void
    {
        if ($this->masterOfCeremony) {
            abort_unless(auth()->user()->canAs('mc.update'), 403);
            $this->authorize('update', $this->masterOfCeremony);
        } else {
            abort_unless(auth()->user()->canAs('mc.create'), 403);
        }

        $maxKb = config('media.max_upload_size_kb', 2048);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'link' => ['nullable', 'string', 'max:255', 'url'],
            'avatarMc' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ]);

        $path = $this->masterOfCeremony?->avatar_mc;

        if ($this->removeExistingAvatar && $this->masterOfCeremony?->avatar_mc) {
            Storage::disk('public')->delete($this->masterOfCeremony->avatar_mc);
            $path = null;
        } elseif ($this->avatarMc) {
            if ($this->masterOfCeremony?->avatar_mc) {
                Storage::disk('public')->delete($this->masterOfCeremony->avatar_mc);
            }
            $path = $this->avatarMc->store('master-of-ceremonies', 'public');
        }

        if ($this->masterOfCeremony) {
            $this->masterOfCeremony->update([
                'name' => $this->name,
                'link' => $this->link ?: null,
                'avatar_mc' => $path,
            ]);
        } else {
            MasterOfCeremony::create([
                'name' => $this->name,
                'link' => $this->link ?: null,
                'avatar_mc' => $path,
            ]);
        }

        $this->redirect(route('master-of-ceremonies.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.master-of-ceremonies.master-of-ceremony-form');
    }
}

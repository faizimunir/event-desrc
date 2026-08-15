<?php

namespace App\Livewire\Riders;

use App\Models\Rider;
use App\Services\MediaService;
use Livewire\Component;
use Livewire\WithFileUploads;

class RiderForm extends Component
{
    use WithFileUploads;

    public ?Rider $rider = null;

    /** When true, flow is from My Rider (member); new riders are linked to the current user. */
    public bool $forMyRider = false;

    public string $name = '';

    public string $nickname = '';

    public string $pob = '';

    public string $dob = '';

    public ?string $gender = null;

    public string $number_plate = '';

    public $photoRider = null;

    public $photoKia = null;

    public function mount(?Rider $rider = null, bool $forMyRider = false): void
    {
        $this->forMyRider = $forMyRider;

        if ($rider?->exists) {
            $this->authorize('update', $rider);
            $this->rider = $rider;
            $this->name = $rider->name;
            $this->nickname = $rider->nickname ?? '';
            $this->pob = $rider->pob ?? '';
            $this->dob = $rider->dob?->format('Y-m-d') ?? '';
            $this->gender = $rider->gender;
            $this->number_plate = $rider->number_plate ?? '';
        } else {
            $user = auth()->user();
            abort_unless(
                $user->canAs('rider.create') || ($forMyRider && $user->canAs('myrider.manage')),
                403
            );
        }
    }

    public function removePhotoRider(): void
    {
        $this->photoRider = null;
    }

    public function removePhotoKia(): void
    {
        $this->photoKia = null;
    }

    public function save(MediaService $mediaService): void
    {
        if ($this->rider) {
            abort_unless(auth()->user()->canAs('rider.update'), 403);
            $this->authorize('update', $this->rider);
        } else {
            $user = auth()->user();
            abort_unless(
                $user->canAs('rider.create') || ($this->forMyRider && $user->canAs('myrider.manage')),
                403
            );
        }

        $maxKb = config('media.max_upload_size_kb', 2048);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'pob' => ['nullable', 'string', 'max:255'],
            'dob' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:boys,girls,other'],
            'number_plate' => ['nullable', 'string', 'max:50'],
            'photoRider' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
            'photoKia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.$maxKb],
        ];

        $this->validate($rules);

        if ($this->rider) {
            $rider = $this->rider;
            $rider->update([
                'name' => $this->name,
                'nickname' => $this->nickname ?: null,
                'pob' => $this->pob ?: null,
                'dob' => $this->dob ?: null,
                'gender' => $this->gender,
                'number_plate' => $this->number_plate ?: null,
            ]);
        } else {
            $attributes = [
                'name' => $this->name,
                'nickname' => $this->nickname ?: null,
                'pob' => $this->pob ?: null,
                'dob' => $this->dob ?: null,
                'gender' => $this->gender,
                'number_plate' => $this->number_plate ?: null,
            ];
            if ($this->forMyRider) {
                $attributes['user_id'] = auth()->id();
            }
            $rider = Rider::create($attributes);
        }

        if ($this->photoRider) {
            $rider->deleteMediaCollection('photo_rider');
            $mediaService->upload($this->photoRider, $rider, 'photo_rider');
        }
        if ($this->photoKia) {
            $rider->deleteMediaCollection('photo_kia');
            $mediaService->upload($this->photoKia, $rider, 'photo_kia');
        }

        $this->redirect(
            $this->returnUrl(),
            navigate: true
        );
    }

    public function returnUrl(): string
    {
        if ($this->forMyRider) {
            return route('my-rider.index');
        }

        if ($this->rider?->exists) {
            return route('riders.show', $this->rider);
        }

        return route('riders.index');
    }

    public function render()
    {
        return view('livewire.riders.rider-form');
    }
}

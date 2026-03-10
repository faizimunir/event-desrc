<?php

namespace App\Livewire\Packages;

use App\Models\Event;
use App\Models\Package;
use App\Models\Reward;
use Livewire\Component;

class PackageForm extends Component
{
    public Event $event;

    public ?Package $package = null;

    /** @var \Illuminate\Database\Eloquent\Collection<int, Reward> */
    public $rewards;

    public string $name = '';

    public string $price = '';

    public ?string $quota = null;

    public int $sort_order = 0;

    /** @var array<int|string, int> */
    public array $rewardsSelected = [];

    public function mount(Event $event, ?Package $package = null): void
    {
        $this->event = $event;
        $this->package = $package;
        $this->rewards = Reward::orderBy('name')->get();

        if ($this->package?->exists) {
            abort_unless(auth()->user()->canAs('package.update'), 403);
            $this->authorize('update', $this->package);
            abort_if($this->package->event_id !== $this->event->id, 404);

            $this->name = $this->package->name;
            $this->price = (string) $this->package->price;
            $this->quota = $this->package->quota !== null ? (string) $this->package->quota : null;
            $this->sort_order = (int) $this->package->sort_order;
            $this->rewardsSelected = $this->package->rewards->pluck('id')->map(fn ($id) => (string) $id)->all();
        } else {
            abort_unless(auth()->user()->canAs('package.create'), 403);
        }
    }

    public function save(): void
    {
        if ($this->package?->exists) {
            abort_unless(auth()->user()->canAs('package.update'), 403);
            $this->authorize('update', $this->package);
        } else {
            abort_unless(auth()->user()->canAs('package.create'), 403);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'quota' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'rewardsSelected' => ['nullable', 'array'],
            'rewardsSelected.*' => ['integer', 'exists:rewards,id'],
        ]);

        $sortOrder = (int) ($validated['sort_order'] ?? 0);
        $rewards = $validated['rewardsSelected'] ?? [];

        $quota = isset($validated['quota']) && $validated['quota'] !== '' ? (int) $validated['quota'] : null;

        if ($this->package?->exists) {
            $this->package->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'quota' => $quota,
                'sort_order' => $sortOrder,
            ]);
            $existingPivot = $this->package->rewards->keyBy('id')->map(fn ($r) => $r->pivot->photo_reward)->all();
            $this->package->rewards()->sync(
                collect($rewards)->mapWithKeys(fn ($id) => [$id => ['photo_reward' => $existingPivot[$id] ?? null]])->all()
            );
            session()->flash('status', __('Package updated.'));
            $this->redirect(route('events.packages.index', $this->event), navigate: true);
        } else {
            $pkg = $this->event->packages()->create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'quota' => $quota,
                'sort_order' => $sortOrder,
            ]);
            if (! empty($rewards)) {
                $pkg->rewards()->sync(collect($rewards)->mapWithKeys(fn ($id) => [$id => ['photo_reward' => null]])->all());
            }
            session()->flash('status', __('Package created.'));
            $this->redirect(route('events.packages.index', $this->event), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.packages.package-form');
    }
}

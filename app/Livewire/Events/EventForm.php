<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\Location;
use App\Models\MasterOfCeremony;
use App\Models\Organizer;
use App\Models\RacingCommittee;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class EventForm extends Component
{
    use WithFileUploads;

    public ?Event $event = null;

    /** @var \Illuminate\Database\Eloquent\Collection<int, Location> */
    public $locations;

    /** @var \Illuminate\Database\Eloquent\Collection<int, RacingCommittee> */
    public $racingCommittees;

    /** @var \Illuminate\Database\Eloquent\Collection<int, MasterOfCeremony> */
    public $masterOfCeremonies;

    /** @var \Illuminate\Database\Eloquent\Collection<int, Organizer> */
    public $organizers;

    public string $title = '';

    public string $category = Event::CATEGORY_UMUR;

    public string $description = '';

    public ?string $organizer_id = null;

    public ?string $racing_committee_id = null;

    public ?string $master_of_ceremony_id = null;

    public string $start_at = '';

    public string $end_at = '';

    public string $status = Event::STATUS_DRAFT;

    public string $registration_opens_at = '';

    public string $registration_closes_at = '';

    public ?string $location_id = null;

    public $poster = null;

    public function mount(?Event $event = null): void
    {
        $this->locations = Location::orderBy('name')->get();
        $this->racingCommittees = RacingCommittee::orderBy('name')->get();
        $this->masterOfCeremonies = MasterOfCeremony::orderBy('name')->get();
        $this->organizers = Organizer::orderBy('name')->get();

        if ($event?->exists) {
            $this->event = $event;
            $this->title = $event->title;
            $this->category = $event->category;
            $this->description = $event->description ?? '';
            $this->organizer_id = $event->organizer_id ? (string) $event->organizer_id : null;
            $this->racing_committee_id = $event->racing_committee_id ? (string) $event->racing_committee_id : null;
            $this->master_of_ceremony_id = $event->master_of_ceremony_id ? (string) $event->master_of_ceremony_id : null;
            $this->start_at = $event->start_at->format('Y-m-d\TH:i');
            $this->end_at = $event->end_at?->format('Y-m-d\TH:i') ?? '';
            $this->status = $event->status;
            $this->registration_opens_at = $event->registration_opens_at?->format('Y-m-d\TH:i') ?? '';
            $this->registration_closes_at = $event->registration_closes_at?->format('Y-m-d\TH:i') ?? '';
            $this->location_id = (string) $event->location_id;
        }
    }

    public function removePoster(): void
    {
        $this->poster = null;
    }

    public function save(): void
    {
        if ($this->event) {
            abort_unless(auth()->user()->canAs('event.update'), 403);
            $this->authorize('update', $this->event);
        } else {
            abort_unless(auth()->user()->canAs('event.create'), 403);
        }

        $this->organizer_id = $this->organizer_id ?: null;
        $this->racing_committee_id = $this->racing_committee_id ?: null;
        $this->master_of_ceremony_id = $this->master_of_ceremony_id ?: null;

        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.Event::CATEGORY_UMUR.','.Event::CATEGORY_TAHUN],
            'description' => ['nullable', 'string'],
            'organizer_id' => ['nullable', 'integer', 'exists:organizers,id'],
            'racing_committee_id' => ['nullable', 'integer', 'exists:racing_committees,id'],
            'master_of_ceremony_id' => ['nullable', 'integer', 'exists:master_of_ceremonies,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', 'in:'.implode(',', Event::STATUSES)],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date'],
            'location_id' => ['nullable'],
            'poster' => ['nullable', 'image', 'max:10240'],
        ];
        if ($this->registration_opens_at && $this->registration_closes_at) {
            $rules['registration_closes_at'][] = 'after_or_equal:registration_opens_at';
        }

        $validated = $this->validate($rules);

        $locationId = $validated['location_id'] ? (int) $validated['location_id'] : null;
        $organizerId = $validated['organizer_id'] ? (int) $validated['organizer_id'] : null;
        $racingCommitteeId = $validated['racing_committee_id'] ? (int) $validated['racing_committee_id'] : null;
        $masterOfCeremonyId = $validated['master_of_ceremony_id'] ? (int) $validated['master_of_ceremony_id'] : null;

        $posterPath = $this->event?->poster;

        if ($this->poster) {
            if ($posterPath && Storage::disk('public')->exists($posterPath)) {
                Storage::disk('public')->delete($posterPath);
            }
            $posterPath = $this->poster->store('events/posters', 'public');
        }

        $registrationOpensAt = $validated['registration_opens_at'] ?: null;
        $registrationClosesAt = $validated['registration_closes_at'] ?: null;

        if ($this->event) {
            $this->event->update([
                'title' => $validated['title'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?: null,
                'organizer_id' => $organizerId,
                'racing_committee_id' => $racingCommitteeId,
                'master_of_ceremony_id' => $masterOfCeremonyId,
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'] ?: null,
                'status' => $validated['status'],
                'registration_opens_at' => $registrationOpensAt,
                'registration_closes_at' => $registrationClosesAt,
                'location_id' => $locationId,
                'poster' => $posterPath ?? $this->event->poster,
            ]);
            $this->redirect(route('events.show', $this->event), navigate: true);
        } else {
            Event::create([
                'title' => $validated['title'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?: null,
                'organizer_id' => $organizerId,
                'racing_committee_id' => $racingCommitteeId,
                'master_of_ceremony_id' => $masterOfCeremonyId,
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'] ?: null,
                'status' => $validated['status'],
                'registration_opens_at' => $registrationOpensAt,
                'registration_closes_at' => $registrationClosesAt,
                'location_id' => $locationId,
                'poster' => $posterPath,
            ]);
            $this->redirect(route('events.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.events.event-form');
    }
}

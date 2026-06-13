<?php

namespace App\Livewire\Events;

use App\Models\Account;
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

    /** @var \Illuminate\Database\Eloquent\Collection<int, Account> */
    public $accounts;

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

    /** @var list<string> */
    public array $payment_methods = [Event::PAYMENT_MANUAL, Event::PAYMENT_QRIS];

    /** @var list<string|int> */
    public array $account_ids = [];

    public $poster = null;

    public $logo = null;

    public $sizeChart = null;

    public bool $show_participants_publicly = true;

    public function mount(?Event $event = null): void
    {
        $this->locations = Location::orderBy('name')->get();
        $this->racingCommittees = RacingCommittee::orderBy('name')->get();
        $this->masterOfCeremonies = MasterOfCeremony::orderBy('name')->get();

        $user = auth()->user();
        $organizerQuery = Organizer::query()->orderBy('name');
        if (! $user->hasRole('super_admin') && ! $user->hasRole('admin') && ! $user->hasRole('committee')) {
            $organizerQuery->where('user_id', $user->id);
        }
        $this->organizers = $organizerQuery->get();
        $this->accounts = Account::orderBy('acc_name')->get();

        if ($event?->exists) {
            $this->event = $event->load('accounts');
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
            $this->location_id = $event->location_id ? (string) $event->location_id : null;
            $this->payment_methods = $event->normalizedPaymentMethods();
            $this->account_ids = $event->accounts->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
            $this->show_participants_publicly = (bool) $event->show_participants_publicly;
        }
    }

    public function removePoster(): void
    {
        $this->poster = null;
    }

    public function removeLogo(): void
    {
        $this->logo = null;
    }

    public function removeSizeChart(): void
    {
        $this->sizeChart = null;
    }

    public function save(): void
    {
        if ($this->event) {
            abort_unless(auth()->user()->canAs('event.update'), 403);
            $this->authorize('update', $this->event);
        } else {
            abort_unless(auth()->user()->canAs('event.create'), 403);
        }

        $user = auth()->user();

        $this->organizer_id = $this->organizer_id ?: null;
        $this->racing_committee_id = $this->racing_committee_id ?: null;
        $this->master_of_ceremony_id = $this->master_of_ceremony_id ?: null;
        $this->location_id = $this->location_id ?: null;
        if (! $this->event && $this->organizer_id === null && ! $user->hasRole('super_admin') && ! $user->hasRole('admin')) {
            $autoOrganizerId = Organizer::where('user_id', $user->id)->value('id');
            if ($autoOrganizerId) {
                $this->organizer_id = (string) $autoOrganizerId;
            }
        }

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
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['in:'.implode(',', Event::PAYMENT_METHODS)],
            'poster' => ['nullable', 'image', 'max:10240'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'sizeChart' => ['nullable', 'image', 'max:10240'],
            'show_participants_publicly' => ['boolean'],
        ];
        if (in_array(Event::PAYMENT_MANUAL, $this->payment_methods ?? [], true)) {
            $rules['account_ids'] = ['required', 'array', 'min:1'];
            $rules['account_ids.*'] = ['integer', 'exists:accounts,id'];
        } else {
            $rules['account_ids'] = ['nullable', 'array'];
            $rules['account_ids.*'] = ['integer', 'exists:accounts,id'];
        }
        if ($this->registration_opens_at && $this->registration_closes_at) {
            $rules['registration_closes_at'][] = 'after_or_equal:registration_opens_at';
        }

        $validated = $this->validate($rules);

        $locationId = $validated['location_id'] ? (int) $validated['location_id'] : null;
        $paymentMethods = array_values(array_unique($validated['payment_methods']));
        $syncAccountIds = in_array(Event::PAYMENT_MANUAL, $paymentMethods, true)
            ? array_values(array_unique(array_map('intval', $validated['account_ids'] ?? [])))
            : [];
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

        $logoPath = $this->event?->logo_url;

        if ($this->logo) {
            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $this->logo->store('events/logos', 'public');
        }

        $sizeChartPath = $this->event?->size_chart;

        if ($this->sizeChart) {
            if ($sizeChartPath && Storage::disk('public')->exists($sizeChartPath)) {
                Storage::disk('public')->delete($sizeChartPath);
            }
            $sizeChartPath = $this->sizeChart->store('events/size-charts', 'public');
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
                'payment_methods' => $paymentMethods,
                'poster' => $posterPath ?? $this->event->poster,
                'logo_url' => $logoPath ?? $this->event->logo_url,
                'size_chart' => $sizeChartPath ?? $this->event->size_chart,
                'show_participants_publicly' => $validated['show_participants_publicly'] ?? true,
            ]);
            $this->event->accounts()->sync($syncAccountIds);
            $this->redirect(route('events.show', $this->event), navigate: true);
        } else {
            $newEvent = Event::create([
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
                'payment_methods' => $paymentMethods,
                'poster' => $posterPath,
                'logo_url' => $logoPath ?? null,
                'size_chart' => $sizeChartPath ?? null,
                'show_participants_publicly' => $validated['show_participants_publicly'] ?? true,
            ]);
            $newEvent->accounts()->sync($syncAccountIds);
            $this->redirect(route('events.index'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.events.event-form');
    }
}

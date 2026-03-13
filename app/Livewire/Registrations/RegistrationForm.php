<?php

namespace App\Livewire\Registrations;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\Team;
use App\Models\User;
use App\Services\RegistrationEligibilityService;
use App\Services\RiderSimilarityService;
use App\Services\WhacenterService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class RegistrationForm extends Component
{
    public Event $event;

    /** @var int|null */
    public $package_id = null;

    public $bracket_id = null;

    /** Nama orang tua (dari form registrasi). */
    public string $parent_name = '';

    /** No WA orang tua (unique per user). */
    public string $whatsapp = '';

    public string $name = '';

    public string $nickname = '';

    public string $pob = '';

    public string $dob = '';

    public string $gender = '';

    public string $number_plate = '';

    public string $teamSearch = '';

    /** @var array<int, int> */
    public array $selectedTeamIds = [];

    /** Rider yang mirip ditemukan; user memilih pakai atau daftar baru. */
    public bool $showSimilarChoice = false;

    /** @var array<int, array{id: int, name: string, dob: string, gender: string}> */
    public array $similarRiders = [];

    /** ID rider yang dipilih user (0 = daftar rider baru). */
    public int $selectedRiderId = 0;

    /** User memilih "daftar rider baru" sehingga skip pengecekan similarity. */
    public bool $skipSimilarityCheck = false;

    public function mount(Event $event): void
    {
        $this->event = $event->load([
            'brackets' => fn ($q) => $q->orderBy('name'),
            'packages',
        ]);
        if ($this->event->packages->count() === 1) {
            $this->package_id = $this->event->packages->first()->id;
        }
    }

    public function updatedTeamSearch(): void
    {
        $this->resetErrorBag('teamSearch');
    }

    /**
     * Teams for pillbox options (backend search). Include selected so they stay visible.
     */
    public function getTeamsProperty()
    {
        $query = Team::query()->orderBy('name');

        if (trim($this->teamSearch) !== '') {
            $query->where('name', 'like', '%'.trim($this->teamSearch).'%')->limit(20);
            return $query->get();
        }

        // When search empty, show selected teams plus maybe a few others
        if (count($this->selectedTeamIds) > 0) {
            $selected = Team::whereIn('id', $this->selectedTeamIds)->orderBy('name')->get();
            $others = Team::whereNotIn('id', $this->selectedTeamIds)->orderBy('name')->limit(15)->get();
            return $selected->merge($others)->unique('id')->values();
        }

        return $query->limit(20)->get();
    }

    public function createTeam(): void
    {
        $name = trim($this->teamSearch);
        if ($name === '') {
            return;
        }

        $team = Team::firstOrCreate(
            ['name' => $name],
            ['name' => $name]
        );

        if (! in_array($team->id, $this->selectedTeamIds, true)) {
            $this->selectedTeamIds[] = $team->id;
        }

        $this->teamSearch = '';
    }

    public function submit(RiderSimilarityService $similarity, RegistrationEligibilityService $eligibility): mixed
    {
        $bracket = $this->event->brackets->firstWhere('id', $this->bracket_id);
        if (! $bracket) {
            $this->addError('bracket_id', __('Invalid bracket.'));
            return null;
        }

        if (! $bracket->hasQuota()) {
            $this->addError('bracket_id', __('This bracket has no remaining quota.'));
            return null;
        }

        $packageId = null;
        if ($this->event->packages->isNotEmpty()) {
            if ($this->event->packages->count() === 1) {
                $pkg = $this->event->packages->first();
                if ($pkg->isQuotaFull()) {
                    $this->addError('package_id', __('This package has no remaining quota.'));
                    return null;
                }
                $packageId = $pkg->id;
            } else {
                $pkg = $this->event->packages->firstWhere('id', $this->package_id);
                if (! $pkg) {
                    $this->addError('package_id', __('Please select a package.'));
                    return null;
                }
                if ($pkg->isQuotaFull()) {
                    $this->addError('package_id', __('This package has no remaining quota.'));
                    return null;
                }
                $packageId = $pkg->id;
            }
        }

        $validated = $this->validate([
            'parent_name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'bracket_id' => ['required', 'exists:event_brackets,id', Rule::in($this->event->brackets->pluck('id')->all())],
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'pob' => ['nullable', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'string', 'in:boys,girls,other'],
            'number_plate' => ['nullable', 'string', 'max:50'],
            'selectedTeamIds' => ['nullable', 'array'],
            'selectedTeamIds.*' => ['integer', 'exists:teams,id'],
        ]);

        if (! $this->skipSimilarityCheck) {
            $similar = $similarity->findSimilarRiders(
                $validated['whatsapp'],
                $validated['name'],
                $validated['nickname'] ?? null,
                $validated['pob'] ?? null,
                $validated['dob'],
                $validated['gender'],
                $validated['number_plate'] ?? null
            );

            if ($similar->isNotEmpty()) {
            $this->similarRiders = $similar->map(fn (array $item) => [
                'id' => $item['rider']->id,
                'score' => $item['score'],
                'name' => $item['rider']->name,
                'nickname' => $item['rider']->nickname,
                'pob' => $item['rider']->pob,
                'dob' => $item['rider']->dob?->format('Y-m-d') ?? '',
                'gender_label' => $item['rider']->gender_label ?? $item['rider']->gender,
                'number_plate' => $item['rider']->number_plate,
            ])->all();
                $this->showSimilarChoice = true;
                return null;
            }
        }

        return $this->createRegistration(null, $validated, $packageId, $bracket, $eligibility);
    }

    /**
     * Konfirmasi: pakai rider yang dipilih (dipanggil dari tombol "Use this profile").
     */
    public function submitConfirm(int $riderId, RegistrationEligibilityService $eligibility): mixed
    {
        $this->selectedRiderId = $riderId;
        $bracket = $this->event->brackets->firstWhere('id', $this->bracket_id);
        if (! $bracket || ! $bracket->hasQuota()) {
            $this->showSimilarChoice = false;
            $this->similarRiders = [];
            $this->addError('bracket_id', __('Invalid bracket or no quota.'));
            return null;
        }

        $validated = $this->validate([
            'parent_name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'bracket_id' => ['required', 'exists:event_brackets,id'],
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'pob' => ['nullable', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'string', 'in:boys,girls,other'],
            'number_plate' => ['nullable', 'string', 'max:50'],
            'selectedTeamIds' => ['nullable', 'array'],
            'selectedTeamIds.*' => ['integer', 'exists:teams,id'],
        ]);

        $pkg = $this->event->packages->count() === 1
            ? $this->event->packages->first()
            : $this->event->packages->firstWhere('id', $this->package_id);
        if ($pkg && $pkg->isQuotaFull()) {
            $this->addError('package_id', __('This package has no remaining quota.'));
            return null;
        }
        $packageId = $pkg?->id;

        return $this->createRegistration($riderId, $validated, $packageId, $bracket, $eligibility);
    }

    public function chooseNewRider(): void
    {
        $this->showSimilarChoice = false;
        $this->similarRiders = [];
        $this->selectedRiderId = 0;
        $this->skipSimilarityCheck = true;
    }

    private function createRegistration(
        ?int $existingRiderId,
        array $validated,
        $packageId,
        $bracket,
        RegistrationEligibilityService $eligibility
    ): mixed {
        $normalizedWa = WhacenterService::normalizeWhatsApp($validated['whatsapp']);
        $user = User::firstOrCreate(
            ['whatsapp' => $normalizedWa],
            [
                'name' => $validated['parent_name'],
                'whatsapp' => $normalizedWa,
            ]
        );
        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        if ($existingRiderId) {
            $rider = Rider::where('id', $existingRiderId)->where('user_id', $user->id)->first();
            if (! $rider) {
                $this->addError('selectedRiderId', __('Invalid rider selection.'));
                return null;
            }
            $rider->update(['user_id' => $user->id]);
        } else {
            $rider = Rider::create([
                'user_id' => $user->id,
                'name' => $validated['name'],
                'nickname' => $validated['nickname'] ?? null,
                'pob' => $validated['pob'] ?? null,
                'dob' => $validated['dob'],
                'gender' => $validated['gender'],
                'number_plate' => $validated['number_plate'] ?? null,
            ]);
        }

        $bracket->load('event');
        $eligibilityCheck = $eligibility->checkEligibility($rider, $bracket);
        if (! $eligibilityCheck['eligible']) {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->addError('dob', $eligibilityCheck['message']);
            return null;
        }

        if (! $bracket->hasQuota()) {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->addError('bracket_id', __('This bracket has no remaining quota.'));
            return null;
        }

        $rider->teams()->sync($this->selectedTeamIds);

        Registration::create([
            'event_id' => $this->event->id,
            'rider_id' => $rider->id,
            'team_ids' => $this->selectedTeamIds,
            'bracket_id' => $bracket->id,
            'package_id' => $packageId,
            'status' => Registration::STATUS_PENDING,
            'number_plate' => $validated['number_plate'] ?? null,
        ]);

        $this->showSimilarChoice = false;
        $this->similarRiders = [];
        $this->selectedRiderId = 0;
        $this->skipSimilarityCheck = false;
        $this->teamSearch = '';
        $this->selectedTeamIds = [];

        return redirect()->route('events.public.show', $this->event->slug)
            ->with('status', __('Registration submitted. You can check status on this page.'));
    }

    public function render()
    {
        return view('livewire.registrations.registration-form');
    }
}

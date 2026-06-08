<?php

namespace App\Livewire\Registrations;

use App\Models\Bracket;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\Team;
use App\Models\User;
use App\Services\MediaService;
use App\Services\QuotaReservationService;
use App\Services\RegistrationEligibilityService;
use App\Services\RiderSimilarityService;
use App\Services\WhacenterService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class EventRegistrationForm extends Component
{
    use WithFileUploads;

    public Event $event;

    /** @var array<int> */
    public array $packageIdsWithJersey = [];

    public ?int $bracket_id = null;

    public ?int $package_id = null;

    public string $parent_name = '';

    public string $whatsapp = '';

    public string $name = '';

    public string $nickname = '';

    public string $pob = '';

    public string $dob = '';

    public string $gender = '';

    public string $number_plate = '';

    public string $jersey_size = '';

    public string $teamSearch = '';

    /** @var array<int, int> */
    public array $selectedTeamIds = [];

    public $photo_kia = null;

    public bool $showSimilarChoice = false;

    /** @var array<int, array<string, mixed>> */
    public array $similarRiders = [];

    public bool $skipSimilarityCheck = false;

    public function mount(Event $event, array $packageIdsWithJersey = []): void
    {
        Order::enforceExpiredDraftsForEvent($event->id);
        Order::enforceExpiredPaymentWindowsForEvent($event->id);

        $this->event = $event->load([
            'brackets' => fn ($q) => $q->orderBy('name'),
            'packages',
        ]);
        $this->packageIdsWithJersey = array_map('intval', $packageIdsWithJersey);
    }

    public function getTeamsProperty()
    {
        $query = Team::query()->orderBy('name');

        if (trim($this->teamSearch) !== '') {
            $query->where('name', 'like', '%'.trim($this->teamSearch).'%')->limit(20);

            return $query->get();
        }

        if (count($this->selectedTeamIds) > 0) {
            $selected = Team::whereIn('id', $this->selectedTeamIds)->orderBy('name')->get();
            $others = Team::whereNotIn('id', $this->selectedTeamIds)->orderBy('name')->limit(15)->get();

            return $selected->merge($others)->unique('id')->values();
        }

        return $query->limit(20)->get();
    }

    public function getSelectedBracketLabelProperty(): string
    {
        return $this->event->brackets->firstWhere('id', $this->bracket_id)?->name ?? '—';
    }

    public function getSelectedPackageLabelProperty(): string
    {
        return $this->event->packages->firstWhere('id', $this->package_id)?->name ?? '—';
    }

    public function getRequiresJerseySizeProperty(): bool
    {
        return $this->package_id !== null
            && in_array($this->package_id, $this->packageIdsWithJersey, true);
    }

    public function removePhotoKia(): void
    {
        $this->photo_kia = null;
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

    public function submit(RiderSimilarityService $similarity, RegistrationEligibilityService $eligibility, MediaService $mediaService): mixed
    {
        if (! $this->validateBracketAndPackage()) {
            return null;
        }

        $this->resolvePendingTeamSearch();

        try {
            $validated = $this->validateFormData();
        } catch (ValidationException $e) {
            $this->handleValidationException($e);

            return null;
        }

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
                $this->dispatch('modal-show', name: 'duplicate-rider-modal');

                return null;
            }
        }

        return $this->createRegistration(null, $validated, $eligibility, $mediaService);
    }

    public function submitConfirm(int $riderId, RegistrationEligibilityService $eligibility, MediaService $mediaService): mixed
    {
        $this->showSimilarChoice = false;
        $this->dispatch('modal-close', name: 'duplicate-rider-modal');

        if (! $this->validateBracketAndPackage()) {
            return null;
        }

        $this->resolvePendingTeamSearch();

        try {
            $validated = $this->validateFormData();
        } catch (ValidationException $e) {
            $this->handleValidationException($e);

            return null;
        }

        return $this->createRegistration($riderId, $validated, $eligibility, $mediaService);
    }

    public function chooseNewRider(): void
    {
        $this->showSimilarChoice = false;
        $this->similarRiders = [];
        $this->skipSimilarityCheck = true;
        $this->dispatch('modal-close', name: 'duplicate-rider-modal');
    }

    public function render()
    {
        return view('livewire.registrations.event-registration-form');
    }

    private function validateBracketAndPackage(): bool
    {
        $bracket = $this->event->brackets->firstWhere('id', $this->bracket_id);
        if (! $bracket) {
            $this->toastError(__('Invalid bracket.'));

            return false;
        }

        if (! $bracket->hasQuota()) {
            $this->toastError(__('This bracket has no remaining quota.'));

            return false;
        }

        if ($this->event->packages->isEmpty()) {
            $this->toastError(__('Package belum tersedia.'));

            return false;
        }

        $activePackageIds = $this->event->packages->where('status', Package::STATUS_ACTIVE)->pluck('id')->all();
        $package = $this->event->packages->firstWhere('id', $this->package_id);
        if (! $package || ! in_array($package->id, $activePackageIds, true)) {
            $this->toastError(__('Please select a package.'));

            return false;
        }

        if ($package->isQuotaFull()) {
            $this->toastError(__('This package has no remaining quota.'));

            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateFormData(): array
    {
        $activePackageIds = $this->event->packages->where('status', Package::STATUS_ACTIVE)->pluck('id')->all();
        $jerseyRules = $this->requiresJerseySize
            ? ['required', 'string', 'in:S,M,L,XL']
            : ['nullable', 'string', 'max:50'];

        return $this->validate([
            'parent_name' => ['required', 'string', 'max:255'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'bracket_id' => ['required', 'exists:event_brackets,id', Rule::in($this->event->brackets->pluck('id')->all())],
            'package_id' => ['required', 'exists:event_packages,id', Rule::in($activePackageIds)],
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:255'],
            'pob' => ['nullable', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'string', 'in:boys,girls,other'],
            'number_plate' => ['required', 'digits_between:1,3'],
            'jersey_size' => $jerseyRules,
            'selectedTeamIds' => ['required', 'array', 'min:1'],
            'selectedTeamIds.*' => ['integer', 'exists:teams,id'],
            'photo_kia' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
        ], [
            'selectedTeamIds.required' => __('Please select or add at least one team.'),
            'selectedTeamIds.min' => __('Please select or add at least one team.'),
        ]);
    }

    private function resolvePendingTeamSearch(): void
    {
        if ($this->selectedTeamIds === [] && trim($this->teamSearch) !== '') {
            $this->createTeam();
        }
    }

    private function createRegistration(
        ?int $existingRiderId,
        array $validated,
        RegistrationEligibilityService $eligibility,
        MediaService $mediaService
    ): mixed {
        $bracket = $this->event->brackets->firstWhere('id', $this->bracket_id);
        $package = $this->event->packages->firstWhere('id', $this->package_id);

        if (! $bracket || ! $package) {
            $this->toastError(__('Invalid bracket or package.'));

            return null;
        }

        $normalizedWa = WhacenterService::normalizeWhatsApp($validated['whatsapp']);
        $user = User::firstOrCreate(
            ['whatsapp' => $normalizedWa],
            ['name' => $validated['parent_name'], 'whatsapp' => $normalizedWa]
        );
        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        if ($existingRiderId) {
            $rider = Rider::where('id', $existingRiderId)->where('user_id', $user->id)->first();
            if (! $rider) {
                $this->toastError(__('Invalid rider selection.'));

                return null;
            }
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

        if ($this->photo_kia) {
            $rider->deleteMediaCollection('photo_kia');
            $mediaService->upload($this->photo_kia, $rider, 'photo_kia');
            $rider->update(['photo_kia' => $rider->getFirstMediaUrl('photo_kia')]);
        }

        $rider->teams()->sync($this->selectedTeamIds);

        $bracket->load('event');
        $eligibilityCheck = $eligibility->checkEligibility($rider, $bracket);
        if (! $eligibilityCheck['eligible']) {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->toastError($eligibilityCheck['message']);

            return null;
        }

        if (! $bracket->hasQuota()) {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->toastError(__('This bracket has no remaining quota.'));

            return null;
        }

        try {
            $order = QuotaReservationService::withLocks(
                $bracket->id,
                $package->id,
                null,
                function () use ($bracket, $package, $rider, $validated) {
                    if (Registration::query()->where('event_id', $this->event->id)
                        ->where('rider_id', $rider->id)
                        ->where('bracket_id', $bracket->id)
                        ->exists()) {
                        return 'duplicate';
                    }

                    $b = Bracket::query()->findOrFail($bracket->id);
                    $p = Package::query()->findOrFail($package->id);
                    if (! $b->hasQuota()) {
                        return 'bracket_quota';
                    }
                    if ($p->isQuotaFull()) {
                        return 'package_quota';
                    }

                    $registration = Registration::create([
                        'event_id' => $this->event->id,
                        'rider_id' => $rider->id,
                        'team_ids' => $this->selectedTeamIds,
                        'bracket_id' => $bracket->id,
                        'package_id' => $package->id,
                        'status' => Registration::STATUS_PENDING,
                        'number_plate' => $validated['number_plate'] ?? null,
                        'jersey_size' => $validated['jersey_size'] ?? null,
                    ]);

                    return Order::create([
                        'registration_id' => $registration->id,
                        'session_id' => session()->getId(),
                        'user_id' => auth()->id(),
                    ]);
                }
            );
        } catch (\Throwable) {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->toastError(__('Registration failed. Please try again.'));

            return null;
        }

        if ($order === 'duplicate') {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->toastError(__('You are already registered for this bracket.'));

            return null;
        }
        if ($order === 'bracket_quota') {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->toastError(__('This bracket has no remaining quota.'));

            return null;
        }
        if ($order === 'package_quota') {
            if (! $existingRiderId) {
                $rider->delete();
            }
            $this->toastError(__('This package has no remaining quota.'));

            return null;
        }

        return redirect()->route('orders.show', $order);
    }

    private function toastError(string $message): void
    {
        $this->dispatch('toast-show',
            duration: 5000,
            slots: ['text' => $message],
            dataset: ['variant' => 'danger'],
        );
    }

    private function handleValidationException(ValidationException $e): void
    {
        // Kalau error-nya murni "kosong/tidak terisi" (selectedTeamIds/photo_kia required),
        // kita biarkan UI menampilkan browser-style "fill the field" (bukan toast).
        $failed = $e->validator->failed(); // [field => [rule => true]]

        $allowedNativeFailures = [
            'selectedTeamIds' => ['required', 'min'],
            'photo_kia' => ['required'],
        ];

        $onlyAllowedNativeFailures = true;
        foreach ($failed as $field => $rules) {
            foreach (array_keys($rules) as $rule) {
                if (! isset($allowedNativeFailures[$field]) || ! in_array($rule, $allowedNativeFailures[$field], true)) {
                    $onlyAllowedNativeFailures = false;
                    break 2;
                }
            }
        }

        if ($onlyAllowedNativeFailures) {
            if (isset($failed['selectedTeamIds'])) {
                $this->dispatch('registration-native-validation', field: 'team');
                return;
            }

            if (isset($failed['photo_kia'])) {
                $this->dispatch('registration-native-validation', field: 'photo_kia');
                return;
            }
        }

        $this->toastError(collect($e->validator->errors()->all())->first());
    }
}

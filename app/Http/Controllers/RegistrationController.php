<?php

namespace App\Http\Controllers;

use App\Exceptions\RegistrationQuotaHttpException;
use App\Models\Bracket;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\Team;
use App\Models\User;
use App\Models\WhatsappNotificationLog;
use App\Services\ManualTransferNotifier;
use App\Services\MediaService;
use App\Services\QuotaReservationService;
use App\Services\RegistrationEligibilityService;
use App\Services\RiderSimilarityService;
use App\Services\TicketService;
use App\Services\WhacenterService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function __construct(
        protected RegistrationEligibilityService $eligibility,
        protected RiderSimilarityService $similarity
    ) {}

    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        return redirect()->route('events.show', [$event, 'tab' => 'registrations']);
    }

    /**
     * Public: store new registration (form di event-show). Cek rider mirip (double), lalu buat/link user + rider.
     */
    public function store(Request $request, Event $event, MediaService $mediaService)
    {
        if ($event->isDraft()) {
            abort(404);
        }
        $hasEarlyAccess = in_array($event->id, session('event_early_access', []), true);
        if (! $event->isRegistrationOpen() && ! $hasEarlyAccess) {
            return redirect()->route('events.public.show', $event->slug)
                ->with('error', __('Registration is not open for this event.'));
        }

        $bracket = $event->brackets()->find($request->input('bracket_id'));
        if (! $bracket) {
            return redirect()->route('events.public.show', $event->slug)->withErrors(['bracket_id' => __('Invalid bracket.')])->withInput();
        }

        if (! $bracket->hasQuota()) {
            return redirect()->route('events.public.show', $event->slug)->withErrors(['bracket_id' => __('This bracket has no remaining quota.')])->withInput();
        }

        if ($event->packages->isEmpty()) {
            return redirect()->route('events.public.show', $event->slug)
                ->with('error', __('Package belum tersedia.'))
                ->withInput();
        }

        $activePackageIds = $event->packages->where('status', Package::STATUS_ACTIVE)->pluck('id')->all();
        $packageRules = ['required', 'exists:event_packages,id', Rule::in($activePackageIds)];
        $packageIdsWithJersey = $event->packages
            ->filter(fn ($package) => $package->hasJerseyReward())
            ->pluck('id')
            ->values()
            ->all();
        $selectedPackageRequiresJersey = in_array((int) $request->input('package_id'), $packageIdsWithJersey, true);
        $jerseyRules = $selectedPackageRequiresJersey
            ? ['required', 'string', Rule::in($event->jerseySizeOptions())]
            : ['nullable', 'string', 'max:50'];

        $this->resolvePendingTeamSearch($request);

        try {
            $validated = $request->validate([
                'parent_name' => ['required', 'string', 'max:255'],
                'whatsapp' => ['required', 'string', 'max:20'],
                'bracket_id' => ['required', 'exists:event_brackets,id', Rule::in($event->brackets->pluck('id')->all())],
                'package_id' => $packageRules,
                'name' => ['required', 'string', 'max:255'],
                'nickname' => ['nullable', 'string', 'max:255'],
                'pob' => ['nullable', 'string', 'max:255'],
                'dob' => ['required', 'date', 'before_or_equal:today'],
                'gender' => ['required', 'string', 'in:boys,girls,other'],
                'number_plate' => ['nullable', 'string', 'max:50'],
                'jersey_size' => $jerseyRules,
                'team_ids' => ['required', 'array', 'min:1'],
                'team_ids.*' => ['integer', 'exists:teams,id'],
                'use_rider_id' => ['nullable', 'integer', 'exists:riders,id'],
                'photo_kia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
            ], [
                'team_ids.required' => __('Please select or add at least one team.'),
                'team_ids.min' => __('Please select or add at least one team.'),
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('events.public.show', $event->slug));
        }

        $package = $event->packages()->find($validated['package_id']);
        if (! $package || ! $package->isActive() || $package->isQuotaFull()) {
            return redirect()->route('events.public.show', $event->slug)
                ->withErrors(['package_id' => $package ? (! $package->isActive() ? __('This package is not available for registration.') : __('This package has no remaining quota.')) : __('Invalid package.')])
                ->withInput();
        }

        $normalizedWa = WhacenterService::normalizeWhatsApp($validated['whatsapp']);

        // Pengecekan rider mirip (nama + DOB + gender + WA): skip hanya jika user sudah pilih profil yang ada
        $useRiderId = $request->filled('use_rider_id') ? (int) $validated['use_rider_id'] : null;

        if ($useRiderId === null) {
            $similar = $this->similarity->findSimilarRiders(
                $validated['whatsapp'],
                $validated['name'],
                $validated['nickname'] ?? null,
                $validated['pob'] ?? null,
                $validated['dob'],
                $validated['gender'],
                $validated['number_plate'] ?? null
            );
            if ($similar->isNotEmpty()) {
                $similarRiders = $similar->map(fn (array $item) => [
                    'id' => $item['rider']->id,
                    'score' => $item['score'],
                    'name' => $item['rider']->name,
                    'nickname' => $item['rider']->nickname,
                    'pob' => $item['rider']->pob,
                    'dob' => $item['rider']->dob?->format('Y-m-d') ?? '',
                    'gender_label' => $item['rider']->gender_label ?? $item['rider']->gender,
                    'number_plate' => $item['rider']->number_plate,
                ])->all();

                return redirect()->route('events.public.show', $event->slug)
                    ->withInput()
                    ->with('similar_riders', $similarRiders)
                    ->with('similar_riders_choice', true);
            }
        }

        $user = User::firstOrCreate(
            ['whatsapp' => $normalizedWa],
            ['name' => $validated['parent_name'], 'whatsapp' => $normalizedWa]
        );
        if (! $user->hasRole('member')) {
            $user->assignRole('member');
        }

        if ($useRiderId !== null) {
            $rider = Rider::where('id', $useRiderId)->where('user_id', $user->id)->first();
            if (! $rider) {
                return redirect()->route('events.public.show', $event->slug)->withErrors(['use_rider_id' => __('Invalid rider selection.')])->withInput();
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

        if ($request->hasFile('photo_kia')) {
            $rider->deleteMediaCollection('photo_kia');
            $mediaService->upload($request->file('photo_kia'), $rider, 'photo_kia');
            $rider->update(['photo_kia' => $rider->getFirstMediaUrl('photo_kia')]);
        }

        $rider->teams()->sync($validated['team_ids'] ?? []);

        $bracket->load('event');
        $eligibility = $this->eligibility->checkEligibility($rider, $bracket);
        if (! $eligibility['eligible']) {
            if ($useRiderId === null) {
                $rider->delete();
            }

            return redirect()->route('events.public.show', $event->slug)->withErrors(['dob' => $eligibility['message']])->withInput();
        }

        if (! $bracket->hasQuota()) {
            if ($useRiderId === null) {
                $rider->delete();
            }

            return redirect()->route('events.public.show', $event->slug)->withErrors(['bracket_id' => __('This bracket has no remaining quota.')])->withInput();
        }

        try {
            $order = QuotaReservationService::withLocks(
                $bracket->id,
                $package->id,
                null,
                function () use ($event, $bracket, $package, $rider, $validated, $request, $selectedPackageRequiresJersey) {
                    if (Registration::query()->where('event_id', $event->id)
                        ->where('rider_id', $rider->id)
                        ->where('bracket_id', $bracket->id)
                        ->exists()) {
                        throw new HttpResponseException(
                            redirect()->route('events.public.show', $event->slug)
                                ->withErrors(['bracket_id' => __('You are already registered for this bracket.')])
                                ->withInput()
                        );
                    }

                    $b = Bracket::query()->findOrFail($bracket->id);
                    $p = Package::query()->findOrFail($package->id);
                    if (! $b->hasQuota()) {
                        throw new RegistrationQuotaHttpException(
                            redirect()->route('events.public.show', $event->slug)
                                ->withErrors(['bracket_id' => __('This bracket has no remaining quota.')])
                                ->withInput(),
                            deleteNewRider: true
                        );
                    }
                    if ($p->isQuotaFull()) {
                        throw new RegistrationQuotaHttpException(
                            redirect()->route('events.public.show', $event->slug)
                                ->withErrors(['package_id' => __('This package has no remaining quota.')])
                                ->withInput(),
                            deleteNewRider: true
                        );
                    }

                    $registration = Registration::create([
                        'event_id' => $event->id,
                        'rider_id' => $rider->id,
                        'team_ids' => $validated['team_ids'] ?? [],
                        'bracket_id' => $bracket->id,
                        'package_id' => $validated['package_id'],
                        'status' => Registration::STATUS_PENDING,
                        'number_plate' => $validated['number_plate'] ?? null,
                        'jersey_size' => $selectedPackageRequiresJersey ? ($validated['jersey_size'] ?? null) : null,
                    ]);

                    return Order::create([
                        'registration_id' => $registration->id,
                        'session_id' => $request->session()->getId(),
                        'user_id' => $request->user()?->id,
                    ]);
                }
            );
        } catch (RegistrationQuotaHttpException $e) {
            if ($useRiderId === null) {
                $rider->delete();
            }
            throw $e;
        }

        return redirect()->route('orders.show', $order);
    }

    /**
     * Admin: form to add a registration internally (pilih rider + bracket + package).
     */
    public function create(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $event->load(['brackets', 'packages' => fn ($q) => $q->where('status', Package::STATUS_ACTIVE)->with('rewards')]);
        $riders = Rider::with('user')->orderBy('name')->get();

        return view('registrations.create', compact('event', 'riders'));
    }

    /**
     * Admin: store a new registration internally (rider + bracket + package).
     */
    public function storeInternal(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $event->loadMissing(['brackets', 'packages.rewards']);
        $activePackageIds = $event->packages->where('status', Package::STATUS_ACTIVE)->pluck('id')->all();
        $packageIdsWithJersey = $event->packages
            ->where('status', Package::STATUS_ACTIVE)
            ->filter(fn ($package) => $package->hasJerseyReward())
            ->pluck('id')
            ->values()
            ->all();
        $selectedPackageRequiresJersey = in_array((int) $request->input('package_id'), $packageIdsWithJersey, true);
        $jerseyRules = $selectedPackageRequiresJersey
            ? ['required', 'string', Rule::in($event->jerseySizeOptions())]
            : ['nullable', 'string', 'max:50'];

        $validated = $request->validate([
            'rider_id' => ['required', 'integer', 'exists:riders,id'],
            'bracket_id' => ['required', 'integer', Rule::in($event->brackets()->pluck('id')->all())],
            'package_id' => ['required', 'integer', Rule::in($activePackageIds)],
            'jersey_size' => $jerseyRules,
        ]);

        $bracket = $event->brackets()->find($validated['bracket_id']);
        $package = $event->packages()->find($validated['package_id']);
        $rider = Rider::findOrFail($validated['rider_id']);

        if (! $package || ! $package->isActive()) {
            return redirect()->route('events.registrations.create', $event)
                ->withErrors(['package_id' => __('This package is not available or has no remaining quota.')])->withInput();
        }

        $registration = QuotaReservationService::withLocks(
            $bracket->id,
            $package->id,
            null,
            function () use ($event, $bracket, $package, $rider, $request, $validated, $selectedPackageRequiresJersey) {
                if (Registration::query()->where('event_id', $event->id)
                    ->where('rider_id', $rider->id)
                    ->where('bracket_id', $bracket->id)
                    ->exists()) {
                    throw new HttpResponseException(
                        redirect()->route('events.registrations.create', $event)
                            ->withErrors(['rider_id' => __('This rider is already registered for this bracket.')])
                            ->withInput()
                    );
                }

                $b = Bracket::query()->findOrFail($bracket->id);
                $p = Package::query()->findOrFail($package->id);
                if (! $b->hasQuota()) {
                    throw new HttpResponseException(
                        redirect()->route('events.registrations.create', $event)
                            ->withErrors(['bracket_id' => __('This bracket has no remaining quota.')])
                            ->withInput()
                    );
                }
                if ($p->isQuotaFull()) {
                    throw new HttpResponseException(
                        redirect()->route('events.registrations.create', $event)
                            ->withErrors(['package_id' => __('This package is not available or has no remaining quota.')])
                            ->withInput()
                    );
                }

                $registration = Registration::create([
                    'event_id' => $event->id,
                    'rider_id' => $rider->id,
                    'bracket_id' => $bracket->id,
                    'package_id' => $package->id,
                    'status' => Registration::STATUS_PENDING,
                    'team_ids' => [],
                    'jersey_size' => $selectedPackageRequiresJersey ? ($validated['jersey_size'] ?? null) : null,
                ]);

                Order::create([
                    'registration_id' => $registration->id,
                    'session_id' => $request->session()->getId(),
                    'user_id' => $request->user()?->id,
                ]);

                return $registration;
            }
        );

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', __('Registration added.'));
    }

    /**
     * Admin: export registrations as CSV (filter by status and latest payment status from query).
     */
    public function export(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        $statusFilter = array_filter((array) $request->query('status', []), fn ($s) => in_array($s, Registration::STATUSES, true));
        $paymentStatusFilter = array_filter((array) $request->query('payment_status', []), fn ($s) => in_array($s, array_merge(\App\Models\Payment::STATUSES, ['none']), true));
        $allowedBracketIds = $event->brackets()->pluck('id')->all();
        $bracketIds = array_values(array_intersect(
            array_map('intval', (array) $request->query('bracket', [])),
            $allowedBracketIds
        ));

        $registrations = $event->registrations()
            ->with(['rider.user', 'bracket', 'package', 'payment'])
            ->when($statusFilter !== [], fn ($q) => $q->whereIn('status', $statusFilter))
            ->when($bracketIds !== [], fn ($q) => $q->whereIn('bracket_id', $bracketIds))
            ->when($paymentStatusFilter !== [], function ($q) use ($paymentStatusFilter) {
                $withStatus = array_diff($paymentStatusFilter, ['none']);
                $includeNone = in_array('none', $paymentStatusFilter);
                $q->where(function ($q) use ($withStatus, $includeNone) {
                    if ($withStatus !== []) {
                        $q->whereHas('payment', fn ($q) => $q->whereIn('status', $withStatus));
                    }
                    if ($includeNone) {
                        $q->orWhereDoesntHave('payment');
                    }
                });
            })
            ->latest()
            ->get();

        $filename = 'registrations-'.str($event->slug)->slug().'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($registrations) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($out, [
                __('Rider'),
                __('Nickname'),
                __('DOB'),
                __('Gender'),
                __('WhatsApp'),
                __('Bracket'),
                __('Package'),
                __('Number plate'),
                __('Jersey size'),
                __('Team'),
                __('Registration status'),
                __('Payment status'),
                __('Amount'),
                __('Registered at'),
            ]);
            foreach ($registrations as $reg) {
                fputcsv($out, [
                    $reg->rider->name ?? '',
                    $reg->rider->nickname ?? '',
                    $reg->rider->dob?->format('Y-m-d') ?? '',
                    $reg->rider->gender_label ?? $reg->rider->gender ?? '',
                    $reg->rider->user?->whatsapp ?? '',
                    $reg->bracket->name ?? '',
                    $reg->package?->name ?? '',
                    $reg->number_plate ?? '',
                    $reg->jersey_size ?? '',
                    Team::whereIn('id', $reg->team_ids ?? [])->pluck('name')->join(', '),
                    $reg->status_label ?? $reg->status ?? '',
                    $reg->payment ? $reg->payment->status_label : __('No payment'),
                    $reg->payment ? 'Rp '.number_format((float) ($reg->payment->transfer_amount ?? $reg->payment->amount), 0, ',', '.') : '',
                    $reg->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Admin: show a single registration.
     */
    public function show(Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }
        $event->loadMissing('brackets');
        $registration->load(['rider.user', 'bracket', 'package', 'payment.reviewedByUser', 'order', 'ticket']);
        $registration->order?->enforceExpiredDraftIfNeeded();
        $registration->order?->enforceExpiredPaymentWindowIfNeeded();
        $registration->load('order');
        if (WhatsappNotificationLog::tableExists()) {
            $registration->load('whatsappNotificationLogs');
        } else {
            $registration->setRelation('whatsappNotificationLogs', collect());
        }

        $needsReviewIds = Registration::where('event_id', $event->id)
            ->where(function ($q) {
                $q->where('status', Registration::STATUS_PENDING)
                    ->orWhereHas('payment', fn ($q) => $q->whereIn('status', [
                        Payment::STATUS_PENDING,
                        Payment::STATUS_SUBMITTED,
                    ]));
            })
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $nextRegistration = null;
        if (count($needsReviewIds) >= 1) {
            $currentPos = array_search($registration->id, $needsReviewIds);
            $nextPos = $currentPos !== false
                ? ($currentPos + 1) % count($needsReviewIds)
                : 0;
            $nextId = $needsReviewIds[$nextPos];
            if ($nextId != $registration->id) {
                $nextRegistration = Registration::find($nextId);
            }
        }

        return view('registrations.show', compact('event', 'registration', 'nextRegistration'));
    }

    /**
     * Admin: kirim ulang pesan WhatsApp e-ticket (template `whatsapp.payment-success`).
     */
    public function resendTicketWhatsapp(Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        $error = TicketService::resendTicketWhatsapp($registration);
        if ($error) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', $error);
        }

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', __('E-ticket WhatsApp message has been queued.'));
    }

    /**
     * Admin: catat klik tombol kirim tiket manual (wa.me) dan kembalikan URL.
     */
    public function sendTicketViaWaMe(Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        [$count, $url, $error] = TicketService::recordManualWaMeTicketClick($registration);
        if ($error || ! $url) {
            if (request()->wantsJson()) {
                return response()->json(['message' => $error ?? __('Unable to open WhatsApp.')], 422);
            }

            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', $error ?? __('Unable to open WhatsApp.'));
        }

        if (request()->wantsJson()) {
            return response()->json([
                'url' => $url,
                'count' => $count,
            ]);
        }

        return redirect()->away($url);
    }

    /**
     * Admin: perbarui nomor WhatsApp akun user rider (dari halaman registrasi).
     */
    public function updateRiderUserWhatsapp(Request $request, Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        $user = $registration->rider?->user;
        if (! $user) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('This registration has no linked user account.'));
        }

        $validated = $request->validate([
            'whatsapp' => ['nullable', 'string', 'max:50'],
        ]);

        $user->forceFill([
            'whatsapp' => $validated['whatsapp'] !== null && $validated['whatsapp'] !== ''
                ? $validated['whatsapp']
                : null,
        ])->save();

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', __('WhatsApp number updated.'));
    }

    /**
     * Admin: update rider profile and this registration’s teams (team_ids + rider pivot sync). Rider must stay eligible for the current bracket.
     */
    public function updateRegistrationRider(Request $request, Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'nickname' => ['nullable', 'string', 'max:255'],
                'pob' => ['nullable', 'string', 'max:255'],
                'dob' => ['required', 'date', 'before_or_equal:today'],
                'gender' => ['required', 'string', 'in:boys,girls,other'],
                'number_plate' => ['nullable', 'string', 'max:50'],
                'team_ids' => ['nullable', 'array'],
                'team_ids.*' => ['integer', 'exists:teams,id'],
                'return_tab' => ['nullable', 'string', 'in:checkin'],
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo($this->registrationRiderEditErrorUrl($request, $event, $registration));
        }

        $registration->loadMissing('bracket.event');
        $rider = $registration->rider;
        $bracket = $registration->bracket;

        $clone = $rider->replicate();
        $clone->fill([
            'name' => $validated['name'],
            'nickname' => $validated['nickname'] ?? null,
            'pob' => $validated['pob'] ?? null,
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'number_plate' => $validated['number_plate'] ?? null,
        ]);
        $eligibilityCheck = $this->eligibility->checkEligibility($clone, $bracket);
        if (! $eligibilityCheck['eligible']) {
            throw ValidationException::withMessages([
                'rider_data' => $eligibilityCheck['message'],
            ])->redirectTo($this->registrationRiderEditErrorUrl($request, $event, $registration));
        }

        $teamIds = array_values(array_unique(array_map('intval', $validated['team_ids'] ?? [])));

        $rider->update([
            'name' => $validated['name'],
            'nickname' => $validated['nickname'] ?? null,
            'pob' => $validated['pob'] ?? null,
            'dob' => $validated['dob'],
            'gender' => $validated['gender'],
            'number_plate' => $validated['number_plate'] ?? null,
        ]);

        $registration->update([
            'team_ids' => $teamIds,
            'number_plate' => $validated['number_plate'] ?? null,
        ]);
        $rider->teams()->sync($teamIds);

        return redirect()->to($this->registrationRiderEditSuccessUrl($request, $event, $registration))
            ->with('status', __('Rider and team details updated.'));
    }

    /**
     * Admin: move a registration to another bracket in the same event.
     */
    public function updateRegistrationBracket(Request $request, Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        try {
            $validated = $request->validate([
                'bracket_id' => ['required', 'integer', Rule::in($event->brackets()->pluck('id')->all())],
            ]);
        } catch (ValidationException $e) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', collect($e->validator->errors()->all())->first() ?: __('Invalid bracket.'));
        }

        $targetBracket = $event->brackets()->with('event')->find($validated['bracket_id']);
        if (! $targetBracket) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('Invalid bracket.'));
        }

        $registration->loadMissing(['rider', 'order']);

        if ((int) $registration->bracket_id === (int) $targetBracket->id) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('status', __('Bracket unchanged.'));
        }

        $eligibilityCheck = $this->eligibility->checkEligibility($registration->rider, $targetBracket);
        if (! $eligibilityCheck['eligible']) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', $eligibilityCheck['message']);
        }

        $result = DB::transaction(function () use ($event, $registration, $targetBracket) {
            collect([$registration->bracket_id, $targetBracket->id])
                ->unique()
                ->sort()
                ->each(fn (int $bracketId) => Bracket::query()->whereKey($bracketId)->lockForUpdate()->firstOrFail());

            $lockedRegistration = Registration::query()
                ->with(['rider', 'order'])
                ->whereKey($registration->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedRegistration->bracket_id === (int) $targetBracket->id) {
                return 'unchanged';
            }

            if (Registration::query()
                ->where('event_id', $event->id)
                ->where('rider_id', $lockedRegistration->rider_id)
                ->where('bracket_id', $targetBracket->id)
                ->whereKeyNot($lockedRegistration->id)
                ->exists()) {
                return 'duplicate';
            }

            $target = Bracket::query()->with('event')->findOrFail($targetBracket->id);
            $eligibilityCheck = $this->eligibility->checkEligibility($lockedRegistration->rider, $target);
            if (! $eligibilityCheck['eligible']) {
                return ['ineligible', $eligibilityCheck['message']];
            }

            if (! $target->hasQuota()) {
                return 'bracket_quota';
            }

            $lockedRegistration->update(['bracket_id' => $target->id]);

            return 'updated';
        });

        if ($result === 'updated') {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('status', __('Registration bracket updated.'));
        }

        if ($result === 'unchanged') {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('status', __('Bracket unchanged.'));
        }

        if ($result === 'duplicate') {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('This rider is already registered for the target bracket.'));
        }

        if ($result === 'bracket_quota') {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('This bracket has no remaining quota.'));
        }

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('error', is_array($result) ? $result[1] : __('Unable to update bracket.'));
    }

    protected function registrationRiderEditSuccessUrl(Request $request, Event $event, Registration $registration): string
    {
        if ($request->input('return_tab') === 'checkin') {
            return route('events.show', ['event' => $event, 'tab' => 'checkin']);
        }

        return route('events.registrations.show', [$event, $registration]);
    }

    protected function registrationRiderEditErrorUrl(Request $request, Event $event, Registration $registration): string
    {
        if ($request->input('return_tab') === 'checkin') {
            return route('events.show', [
                'event' => $event,
                'tab' => 'checkin',
                'edit_registration' => $registration->id,
            ]);
        }

        return route('events.registrations.show', [$event, $registration]);
    }

    /**
     * Admin: update registration status (approve / reject / cancel).
     */
    public function updateStatus(Request $request, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Registration::STATUSES)],
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['status'] === Registration::STATUS_REJECTED) {
            $hadPaid = false;
            $reason = $validated['rejection_reason'] ?? null;
            DB::transaction(function () use ($registration, &$hadPaid, $reason) {
                $order = Order::query()->where('registration_id', $registration->id)->lockForUpdate()->first();
                if ($order) {
                    $hadPaid = $order->payments()->where('status', Payment::STATUS_SUCCESS)->exists();
                    $order->update(['status' => Order::STATUS_CANCELLED]);
                    if ($hadPaid) {
                        $order->payments()->where('status', Payment::STATUS_SUCCESS)->update([
                            'status' => Payment::STATUS_REFUNDED,
                        ]);
                    }
                    $latestId = (int) $order->payments()->max('id');
                    $order->payments()
                        ->whereIn('status', [
                            Payment::STATUS_PENDING,
                            Payment::STATUS_SUBMITTED,
                            Payment::STATUS_FAILED,
                        ])
                        ->when($latestId > 0, fn ($q) => $q->where('id', '!=', $latestId))
                        ->update(['status' => Payment::STATUS_VOID]);
                    if ($latestId > 0) {
                        $latestAttrs = filled($reason)
                            ? [
                                'status' => Payment::STATUS_FAILED,
                                'admin_notes' => $reason,
                                'reviewed_at' => now(),
                                'reviewed_by' => auth()->id(),
                            ]
                            : ['status' => Payment::STATUS_VOID];
                        $order->payments()
                            ->whereKey($latestId)
                            ->whereIn('status', [
                                Payment::STATUS_PENDING,
                                Payment::STATUS_SUBMITTED,
                                Payment::STATUS_FAILED,
                            ])
                            ->update($latestAttrs);
                    }
                }
                $registration->update(['status' => Registration::STATUS_REJECTED]);
            });
            $registration->refresh();
            ManualTransferNotifier::registrationRejected(
                $registration,
                $hadPaid,
                $reason
            );

            return redirect()->route('events.registrations.show', [$registration->event, $registration])
                ->with('status', __('Registration status updated.'));
        }

        $registration->update(['status' => $validated['status']]);

        if ($validated['status'] === Registration::STATUS_APPROVED) {
            TicketService::ensureTicketForRegistration($registration);
        }

        return redirect()->route('events.registrations.show', [$registration->event, $registration])
            ->with('status', __('Registration status updated.'));
    }

    /**
     * Admin: buat ulang percobaan bayar untuk order batal tanpa baris payment, setelah cek kuota.
     * Metode manual / QRIS mengikuti pilihan (wajib jika keduanya tersedia).
     */
    public function generatePayment(Request $request, Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        if ($registration->isRejected()) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('Cannot generate payment for a rejected registration.'));
        }

        $order = $registration->order;
        if (! $order || ! $order->isCancelled()) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('Payment can only be generated when the order is cancelled.'));
        }

        if ($registration->payment) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('A payment record already exists for this registration.'));
        }

        $event->loadMissing('accounts');
        $allowsManual = $event->allowsManualPayment() && $event->accounts->isNotEmpty();
        $allowsQris = $event->allowsQrisPayment();

        if (! $allowsManual && ! $allowsQris) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('No payment method is configured for this event.'));
        }

        if ($allowsManual && ! $allowsQris) {
            $paymentMethod = 'manual';
        } elseif (! $allowsManual && $allowsQris) {
            $paymentMethod = 'qris';
        } else {
            $validated = $request->validate([
                'payment_method' => ['required', Rule::in(['manual', 'qris'])],
            ]);
            $paymentMethod = $validated['payment_method'];
        }

        if ($paymentMethod === 'manual' && ! $allowsManual) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('Manual transfer is not available for this event.'));
        }
        if ($paymentMethod === 'qris' && ! $allowsQris) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('QRIS / automatic payment is not available for this event.'));
        }

        $error = QuotaReservationService::withLocks(
            $registration->bracket_id,
            $registration->package_id,
            $order->getKey(),
            function () use ($registration, $order, $paymentMethod) {
                $bracket = Bracket::query()->findOrFail($registration->bracket_id);
                $package = Package::query()->findOrFail($registration->package_id);
                if (! $bracket->hasQuota() || $package->isQuotaFull()) {
                    return __('There is no remaining quota for this bracket or package.');
                }

                if ($registration->isCancelled()) {
                    $registration->update(['status' => Registration::STATUS_PENDING]);
                }

                $baseAmount = $registration->package ? (float) $registration->package->price : 0.0;
                $adminFeeAmount = ($registration->package && ! $registration->package->adminFeeIsIncludedInPrice())
                    ? (float) $registration->package->admin_fee
                    : 0.0;
                $minutes = Payment::PAYMENT_PROOF_DEADLINE_MINUTES;
                $expiry = now()->addMinutes($minutes);

                $order->forceFill([
                    'status' => Order::STATUS_UNPAID,
                    'expired_at' => $expiry,
                    'confirmed_at' => now(),
                ])->save();

                if ($paymentMethod === 'manual') {
                    $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                    $order->createNewPaymentAttempt([
                        'amount' => $components['amount'],
                        'admin_fee_amount' => $components['admin_fee_amount'],
                        'unique_code' => $components['unique_code'],
                        'transfer_amount' => $components['transfer_amount'],
                        'method' => 'manual',
                        'status' => Payment::STATUS_PENDING,
                        'expires_at' => $expiry,
                    ]);
                } else {
                    $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);
                    $order->createNewPaymentAttempt([
                        'amount' => $components['amount'],
                        'admin_fee_amount' => $components['admin_fee_amount'],
                        'unique_code' => $components['unique_code'],
                        'transfer_amount' => $components['transfer_amount'],
                        'method' => Payment::METHOD_QRIS,
                        'status' => Payment::STATUS_PENDING,
                        'expires_at' => $expiry,
                    ]);
                }

                return null;
            }
        );

        if ($error) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', $error);
        }

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', __('Payment generated. The participant can pay using the usual link.'));
    }

    /**
     * Admin: buka kembali alur bayar (payment expired/failed/void) setelah cek kuota.
     */
    public function reopenPayment(Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        if ($registration->isRejected()) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('Cannot reopen payment for a rejected registration.'));
        }

        $payment = $registration->payment;
        if (! $payment || ! in_array($payment->status, [
            Payment::STATUS_EXPIRED,
            Payment::STATUS_FAILED,
            Payment::STATUS_VOID,
        ], true)) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('The latest payment is not in a state that can be reopened.'));
        }

        $order = $registration->order;
        if (! $order) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('No order for this registration.'));
        }

        $error = QuotaReservationService::withLocks(
            $registration->bracket_id,
            $registration->package_id,
            $order->getKey(),
            function () use ($registration, $order) {
                $bracket = Bracket::query()->findOrFail($registration->bracket_id);
                $package = Package::query()->findOrFail($registration->package_id);
                if (! $bracket->hasQuota() || $package->isQuotaFull()) {
                    return __('There is no remaining quota for this bracket or package.');
                }

                if ($registration->isCancelled()) {
                    $registration->update(['status' => Registration::STATUS_PENDING]);
                }

                $baseAmount = $registration->package ? (float) $registration->package->price : 0.0;
                $adminFeeAmount = ($registration->package && ! $registration->package->adminFeeIsIncludedInPrice())
                    ? (float) $registration->package->admin_fee
                    : 0.0;
                $minutes = Payment::PAYMENT_PROOF_DEADLINE_MINUTES;
                $expiry = now()->addMinutes($minutes);
                $components = Payment::buildTransferComponentsForOrder($order, $baseAmount, $adminFeeAmount);

                $order->forceFill([
                    'status' => Order::STATUS_UNPAID,
                    'expired_at' => $expiry,
                    'confirmed_at' => now(),
                ])->save();

                $order->createNewPaymentAttempt([
                    'amount' => $components['amount'],
                    'admin_fee_amount' => $components['admin_fee_amount'],
                    'unique_code' => $components['unique_code'],
                    'transfer_amount' => $components['transfer_amount'],
                    'method' => 'manual',
                    'status' => Payment::STATUS_PENDING,
                    'expires_at' => $expiry,
                ]);

                return null;
            }
        );

        if ($error) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', $error);
        }

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', __('Payment window reopened. A new payment attempt was created if quota is available.'));
    }

    /**
     * Admin: reset payment / upload proof deadline (order expired by time or scheduler).
     */
    public function resetPaymentDeadline(Event $event, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        if ($registration->event_id !== $event->id) {
            abort(404);
        }

        $order = $registration->order;
        if (! $order) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', __('No order for this registration.'));
        }

        $error = $order->resetPaymentDeadlineForAdmin();
        if ($error) {
            return redirect()->route('events.registrations.show', [$event, $registration])
                ->with('error', $error);
        }

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', __('Payment deadline has been reset. The participant can use the payment link again; if the order was fully expired, they may receive a new unique transfer amount.'));
    }

    /**
     * Admin: approve registration and payment in one action.
     */
    public function approveAll(Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $event = $registration->event;
        $messages = [];

        if (! $registration->isApproved()) {
            $registration->update(['status' => Registration::STATUS_APPROVED]);
            TicketService::ensureTicketForRegistration($registration);
            $messages[] = __('Registration approved.');
        }

        $payment = $registration->payment;
        if ($payment && ($payment->isPending() || $payment->isSubmitted())) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);
            $payment->order?->update([
                'status' => Order::STATUS_PAID,
                'paid_at' => now(),
            ]);
            TicketService::ensureTicketForRegistration($registration);
            $messages[] = __('Payment approved.');
        }

        $message = $messages !== [] ? implode(' ', $messages) : __('Already approved.');

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', $message);
    }

    /**
     * If user typed a team name but did not click Create, auto-add it before validation.
     */
    private function resolvePendingTeamSearch(Request $request): void
    {
        $teamIds = array_values(array_filter(array_map('intval', (array) $request->input('team_ids', []))));

        if ($teamIds !== []) {
            return;
        }

        $pendingSearch = trim((string) $request->input('team_search_pending', ''));
        if ($pendingSearch === '') {
            return;
        }

        $team = Team::firstOrCreate(
            ['name' => $pendingSearch],
            ['name' => $pendingSearch]
        );

        $request->merge(['team_ids' => [$team->id]]);
    }
}

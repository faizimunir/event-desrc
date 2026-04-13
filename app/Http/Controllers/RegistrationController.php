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
use App\Services\MediaService;
use App\Services\QuotaReservationService;
use App\Services\RegistrationEligibilityService;
use App\Services\RiderSimilarityService;
use App\Services\TicketService;
use App\Services\WhacenterService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function __construct(
        protected RegistrationEligibilityService $eligibility,
        protected RiderSimilarityService $similarity
    ) {}

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
                'jersey_size' => ['nullable', 'string', 'max:50'],
                'team_ids' => ['nullable', 'array'],
                'team_ids.*' => ['integer', 'exists:teams,id'],
                'use_rider_id' => ['nullable', 'integer', 'exists:riders,id'],
                'photo_kia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.(config('media.max_upload_size_kb', 2048))],
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
                function () use ($event, $bracket, $package, $rider, $validated, $request) {
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
                        'jersey_size' => $validated['jersey_size'] ?? null,
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

        $event->load(['brackets', 'packages' => fn ($q) => $q->where('status', Package::STATUS_ACTIVE)]);
        $riders = Rider::with('user')->orderBy('name')->get();

        return view('registrations.create', compact('event', 'riders'));
    }

    /**
     * Admin: store a new registration internally (rider + bracket + package).
     */
    public function storeInternal(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $validated = $request->validate([
            'rider_id' => ['required', 'integer', 'exists:riders,id'],
            'bracket_id' => ['required', 'integer', Rule::in($event->brackets()->pluck('id')->all())],
            'package_id' => ['required', 'integer', Rule::in($event->packages()->where('status', Package::STATUS_ACTIVE)->pluck('id')->all())],
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
            function () use ($event, $bracket, $package, $rider, $request) {
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
     * Admin: export registrations as CSV (filter by status and payment_status from query).
     */
    public function export(Request $request, Event $event)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        $statusFilter = array_filter((array) $request->query('status', []), fn ($s) => in_array($s, Registration::STATUSES, true));
        $paymentStatusFilter = array_filter((array) $request->query('payment_status', []), fn ($s) => in_array($s, array_merge(\App\Models\Payment::STATUSES, ['none']), true));

        $registrations = $event->registrations()
            ->with(['rider.user', 'bracket', 'package', 'payment'])
            ->when($statusFilter !== [], fn ($q) => $q->whereIn('status', $statusFilter))
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
                    $reg->payment ? 'Rp '.number_format($reg->payment->amount, 0, ',', '.') : '',
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
        $registration->load(['rider.user', 'bracket', 'package', 'payment.reviewedByUser', 'order', 'ticket']);
        if (WhatsappNotificationLog::tableExists()) {
            $registration->load('whatsappNotificationLogs');
        } else {
            $registration->setRelation('whatsappNotificationLogs', collect());
        }

        $needsReviewIds = Registration::where('event_id', $event->id)
            ->where(function ($q) {
                $q->where('status', Registration::STATUS_PENDING)
                    ->orWhereHas('payment', fn ($q) => $q->where('status', Payment::STATUS_PENDING));
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
     * Admin: update registration status (approve / reject / cancel).
     */
    public function updateStatus(Request $request, Registration $registration)
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(Registration::STATUSES)],
        ]);

        $registration->update(['status' => $validated['status']]);

        if ($validated['status'] === Registration::STATUS_APPROVED) {
            TicketService::ensureTicketForRegistration($registration);
        }

        return redirect()->route('events.registrations.show', [$registration->event, $registration])
            ->with('status', __('Registration status updated.'));
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
        if ($payment && $payment->isPending()) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);
            $payment->order?->update([
                'status' => Order::STATUS_CONFIRMED,
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'paid_at' => now(),
            ]);
            TicketService::ensureTicketForRegistration($registration);
            $messages[] = __('Payment approved.');
        }

        $message = $messages !== [] ? implode(' ', $messages) : __('Already approved.');

        return redirect()->route('events.registrations.show', [$event, $registration])
            ->with('status', $message);
    }
}

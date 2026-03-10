<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\User;
use App\Services\RegistrationEligibilityService;
use App\Services\RiderSimilarityService;
use App\Services\WhacenterService;
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
    public function store(Request $request, Event $event)
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

        $packageRules = $event->packages->isNotEmpty()
            ? ['required', 'exists:event_packages,id', Rule::in($event->packages->pluck('id')->all())]
            : ['nullable', 'exists:event_packages,id', Rule::in($event->packages->pluck('id')->all())];

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
                'use_rider_id' => ['nullable', 'integer', 'exists:riders,id'],
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('events.public.show', $event->slug));
        }

        if (! empty($validated['package_id'])) {
            $package = $event->packages()->find($validated['package_id']);
            if ($package && $package->isQuotaFull()) {
                return redirect()->route('events.public.show', $event->slug)
                    ->withErrors(['package_id' => __('This package has no remaining quota.')])
                    ->withInput();
            }
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

        $organizerIds = array_values(array_unique(array_filter(array_map('intval', $request->input('organizer_ids', [])))));
        if (! empty($organizerIds)) {
            $rider->organizers()->sync($organizerIds);
        }

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

        $existingRegistration = Registration::where('event_id', $event->id)
            ->where('rider_id', $rider->id)
            ->where('bracket_id', $bracket->id)
            ->first();
        if ($existingRegistration) {
            return redirect()->route('events.public.show', $event->slug)
                ->withErrors(['bracket_id' => __('You are already registered for this bracket.')])
                ->withInput();
        }

        $registration = Registration::create([
            'event_id' => $event->id,
            'rider_id' => $rider->id,
            'bracket_id' => $bracket->id,
            'package_id' => $validated['package_id'] ?? null,
            'status' => Registration::STATUS_PENDING,
            'number_plate' => $validated['number_plate'] ?? null,
        ]);

        $order = Order::create([
            'registration_id' => $registration->id,
            'session_id' => $request->session()->getId(),
            'user_id' => $request->user()?->id,
        ]);

        return redirect()->route('events.public.show', $event->slug)
            ->with('status', __('Registration submitted. You can check status on this page.'))
            ->with('order_id', $order->id);
    }

    /**
     * Admin: list registrations for an event.
     */
    public function index(Event $event)
    {
        abort_unless(auth()->user()->canAs('event.read'), 403);

        $event->load(['registrations.rider', 'registrations.bracket']);
        $registrations = $event->registrations()->with(['rider.organizers', 'rider.user', 'bracket', 'payment'])->latest()->paginate(20);

        return view('registrations.index', compact('event', 'registrations'));
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

        return redirect()->route('events.registrations.index', $registration->event_id)
            ->with('status', __('Registration status updated.'));
    }
}

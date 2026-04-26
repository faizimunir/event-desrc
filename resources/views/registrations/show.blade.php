@php
    $rider = $registration->rider;
    $parent = $rider->user;
    $riderNumberPlate = filled($registration->number_plate) ? $registration->number_plate : $rider->number_plate;
    $registrationTeamIds = collect($registration->team_ids ?? [])
        ->map(fn ($id) => (int) $id)
        ->filter(fn ($id) => $id > 0)
        ->unique()
        ->values()
        ->all();
    $riderBoxTeams = $registrationTeamIds !== []
        ? \App\Models\Team::query()->whereIn('id', $registrationTeamIds)->orderBy('name')->get()
        : collect();
    $payment = $registration->payment;
    $photoKiaUrl = $rider->getFirstMediaUrl('photo_kia') ?: $rider->photo_kia;
    $transferProofUrl = $payment?->transfer_proof_url;
    $badgeColor = match ($registration->status) {
        'approved' => 'green',
        'pending' => 'yellow',
        'rejected' => 'red',
        'cancelled' => 'zinc',
        default => 'zinc',
    };
    $payBadgeColor = $payment ? match ($payment->status) {
        'success' => 'green',
        'pending', 'submitted' => 'yellow',
        'failed' => 'red',
        'void', 'refunded', 'expired', 'cancelled' => 'zinc',
        default => 'zinc',
    } : null;
    $canUpdate = auth()->user()->canAs('event.update');
    $openEditRiderDataModal = $canUpdate && (
        $errors->hasAny([
            'name', 'nickname', 'pob', 'dob', 'gender', 'number_plate', 'rider_data', 'team_ids',
        ])
        || collect($errors->keys())->contains(fn (string $k) => str_starts_with($k, 'team_ids.'))
    );
    $showStatusActions = $canUpdate && in_array($registration->status, ['pending', 'approved', 'rejected'], true);
    $canApproveAll = $canUpdate && (! $registration->isApproved() || ($payment && ($payment->isPending() || $payment->isSubmitted())));
    $canReopenPayment = $canUpdate
        && $registration->order
        && $payment
        && in_array($payment->status, ['expired', 'failed', 'void'], true)
        && ! $registration->isRejected();
    $order = $registration->order;
    $canResetPaymentDeadline = $canUpdate && $order && $order->adminCanResetPaymentDeadline($registration);
    $waNumber = $rider->user?->whatsapp ? \App\Services\WhacenterService::normalizeWhatsApp($rider->user->whatsapp) : '';
    $paymentLinkUrl = $registration->order
        ? route('payment.create', array_filter([
            'order_code' => $registration->order->order_code,
            'whatsapp' => $rider->user?->whatsapp,
            'payment_method' => $event->allowsQrisPayment() ? 'qris' : ($event->allowsManualPayment() ? 'manual' : null),
        ], static fn ($v) => $v !== null && $v !== ''))
        : '';
    $waSendPaymentUrl = $waNumber && $paymentLinkUrl ? 'https://wa.me/'.$waNumber.'?text='.rawurlencode($paymentLinkUrl) : '';

    $event->loadMissing('accounts');
    $eventAllowsManual = $event->allowsManualPayment() && $event->accounts->isNotEmpty();
    $eventAllowsQris = $event->allowsQrisPayment();
    $canGeneratePayment = $canUpdate
        && $order
        && $order->isCancelled()
        && ! $payment
        && ! $registration->isRejected()
        && ($eventAllowsManual || $eventAllowsQris);
    $generatePaymentBothOptions = $eventAllowsManual && $eventAllowsQris;

    $ticket = $registration->ticket;
    $eTicketUrl = $ticket ? route('tickets.show', $ticket->ticket_code, true) : null;
    $canResendEticketWhatsapp = $canUpdate && $ticket && filled($rider->user?->whatsapp);

    $registrationActivityLog = collect([
        [
            'at' => $registration->created_at,
            'title' => __('Registration submitted'),
            'detail' => null,
        ],
    ]);

    if ($order) {
        $registrationActivityLog->push([
            'at' => $order->created_at,
            'title' => __('Order created'),
            'detail' => $order->order_code,
        ]);
        if ($order->confirmed_at) {
            $registrationActivityLog->push([
                'at' => $order->confirmed_at,
                'title' => __('Order confirmed'),
                'detail' => null,
            ]);
        }
    }

    if ($payment) {
        $payDetailParts = array_filter([$payment->status_label, $transferProofUrl ? __('Proof attached') : null]);
        $registrationActivityLog->push([
            'at' => $payment->created_at,
            'title' => __('Payment record'),
            'detail' => $payDetailParts !== [] ? implode(' · ', $payDetailParts) : null,
        ]);
        if ($transferProofUrl && $payment->updated_at->gt($payment->created_at->copy()->addSeconds(90))) {
            $registrationActivityLog->push([
                'at' => $payment->updated_at,
                'title' => __('Transfer proof updated'),
                'detail' => null,
            ]);
        }
        if ($payment->isSuccess() && ($payment->paid_at || $payment->reviewed_at)) {
            $by = $payment->reviewedByUser?->name;
            $registrationActivityLog->push([
                'at' => $payment->paid_at ?? $payment->reviewed_at,
                'title' => __('Payment successful'),
                'detail' => $by ? __('Reviewed by :name', ['name' => $by]) : null,
            ]);
        } elseif ($payment->isFailed()) {
            $registrationActivityLog->push([
                'at' => $payment->updated_at,
                'title' => __('Payment rejected'),
                'detail' => null,
            ]);
        } elseif ($payment->isExpired()) {
            $registrationActivityLog->push([
                'at' => $payment->updated_at,
                'title' => __('Payment expired'),
                'detail' => null,
            ]);
        } elseif ($payment->isCancelled()) {
            $registrationActivityLog->push([
                'at' => $payment->updated_at,
                'title' => __('Payment cancelled'),
                'detail' => null,
            ]);
        }
    }

    if ($order && $order->paid_at && $order->isPaid() && ! ($payment && $payment->isSuccess())) {
        $registrationActivityLog->push([
            'at' => $order->paid_at,
            'title' => __('Order marked paid'),
            'detail' => null,
        ]);
    }

    if ($ticket) {
        $registrationActivityLog->push([
            'at' => $ticket->created_at,
            'title' => __('Ticket issued'),
            'detail' => $ticket->ticket_code,
        ]);
    }

    if ($registration->updated_at->gt($registration->created_at->copy()->addMinute())) {
        $registrationActivityLog->push([
            'at' => $registration->updated_at,
            'title' => __('Registration record updated'),
            'detail' => __('Status: :status', ['status' => $registration->status_label]),
        ]);
    }

    foreach ($registration->whatsappNotificationLogs as $waLog) {
        $registrationActivityLog->push($waLog->activityTimelineRow());
    }

    $registrationActivityLog = $registrationActivityLog
        ->sortBy(fn (array $row) => $row['at']->timestamp)
        ->values();
@endphp
<x-layouts::app :title="__('Registration') . ' — ' . $event->title">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', [$event, 'tab' => 'registrations'])" wire:navigate>{{ __('Registrations') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $rider->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-wrap items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'registrations'])" wire:navigate icon="arrow-left">
                {{ __('Back to event') }}
            </flux:button>
            @if ($registration->order)
                <flux:button variant="ghost" size="sm" :href="route('orders.show', $registration->order)" wire:navigate icon="document-text">
                    {{ __('View order') }}
                </flux:button>
            @endif
            @if ($payment && ($payment->isPending() || $payment->isSubmitted()))
                <flux:button variant="ghost" size="sm" :href="route('payments.index', ['status' => 'pending'])" wire:navigate icon="banknotes">
                    {{ __('Payments') }}
                </flux:button>
            @endif
        </div>

        {{-- Header: nama + status --}}
        <div class="flex flex-wrap items-center justify-between gap-4">
            <flux:heading>{{ $rider->name }}@if ($rider->nickname) <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ $rider->nickname }})</span>@endif</flux:heading>
            <flux:badge :color="$badgeColor" size="lg">{{ $registration->status_label }}</flux:badge>
        </div>


        {{-- Dokumen verifikasi: Photo KIA + Bukti Transfer (prioritas untuk admin) --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
                <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Registration Detail') }}</h2>
                </div>
                <dl class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="flex flex-wrap items-center justify-between gap-2 text-sm font-medium text-zinc-500 dark:text-zinc-400 sm:block">
                            <span>{{ __('Rider') }}</span>
                        </dt>
                        <dd class="mt-1 space-y-1.5 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                            <p>
                                <span class="font-medium">{{ $rider->name }}</span>
                                @if ($rider->nickname)
                                    <span class="text-zinc-500 dark:text-zinc-400">({{ $rider->nickname }})</span>
                                @endif
                            </p>
                            @if ($rider->dob)
                                <p class="text-zinc-600 dark:text-zinc-300">
                                    {{ $rider->dob->format('d/m/Y') }}
                                    @if (($age = $rider->ageOn()) !== null)
                                        <span class="text-zinc-500 dark:text-zinc-400">· {{ trans_choice(':count year|:count years', $age, ['count' => $age]) }}</span>
                                    @endif
                                </p>
                            @endif
                            @if (filled($rider->pob))
                                <p class="text-zinc-600 dark:text-zinc-300">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Place of birth') }}:</span>
                                    {{ $rider->pob }}
                                </p>
                            @endif
                            @if ($rider->gender)
                                <p class="text-zinc-600 dark:text-zinc-300">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}:</span>
                                    {{ $rider->gender_label ?? $rider->gender }}
                                </p>
                            @endif
                            @if (filled($riderNumberPlate))
                                <p class="text-zinc-600 dark:text-zinc-300">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}:</span>
                                    {{ $riderNumberPlate }}
                                </p>
                            @endif
                            <p class="text-zinc-600 dark:text-zinc-300">
                                <span class="text-zinc-500 dark:text-zinc-400">{{ __('Teams') }}:</span>
                                @if ($riderBoxTeams->isNotEmpty())
                                    {{ $riderBoxTeams->pluck('name')->join(', ') }}
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                @endif
                            </p>
                            @if ($canUpdate)
                                <flux:modal.trigger name="edit-rider-data">
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        size="xs"
                                        class="!min-h-0 shrink-0 !px-1 !py-0.5 sm:mt-1"
                                        icon="pencil-square"
                                        title="{{ __('Edit rider') }}"
                                    ></flux:button>
                                </flux:modal.trigger>
                            @endif
                        </dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $registration->bracket->name }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $registration->package?->name ?? '—' }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Registered at') }}</dt>
                        <dd class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 sm:col-span-2 sm:mt-0">{{ $registration->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Parent') }}</dt>
                        <dd class="mt-1 space-y-1.5 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                            @if ($parent)
                                <p class="font-medium">{{ $parent->name }}</p>
                                @if (filled($parent->email))
                                    <p class="text-zinc-600 dark:text-zinc-300 break-all">{{ $parent->email }}</p>
                                @else
                                    <p class="text-zinc-500 dark:text-zinc-400">{{ __('No email yet') }}</p>
                                @endif
                                @if (filled($parent->phone))
                                    <p class="text-zinc-600 dark:text-zinc-300">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}:</span>
                                        {{ $parent->phone }}
                                    </p>
                                @endif
                                @if ($parent->whatsapp)
                                    <p class="flex flex-wrap items-center gap-1 text-zinc-600 dark:text-zinc-300">
                                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('WhatsApp') }}:</span>
                                        <span>{{ $parent->whatsapp }}</span>
                                        @if ($canUpdate)
                                            <flux:modal.trigger name="edit-rider-whatsapp">
                                                <flux:button
                                                    type="button"
                                                    variant="ghost"
                                                    size="xs"
                                                    class="!min-h-0 shrink-0 !px-1 !py-0.5"
                                                    icon="pencil-square"
                                                    title="{{ __('Edit WhatsApp') }}"
                                                ></flux:button>
                                            </flux:modal.trigger>
                                        @endif
                                    </p>
                                @elseif ($canUpdate)
                                    <p class="flex flex-wrap items-center gap-1 text-zinc-500 dark:text-zinc-400">
                                        <span>{{ __('No WhatsApp') }}</span>
                                        <flux:modal.trigger name="edit-rider-whatsapp">
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                size="xs"
                                                class="!min-h-0 shrink-0 !px-1 !py-0.5"
                                                icon="pencil-square"
                                                title="{{ __('Add WhatsApp') }}"
                                            ></flux:button>
                                        </flux:modal.trigger>
                                    </p>
                                @endif
                            @else
                                <p class="text-zinc-500 dark:text-zinc-400">{{ __('No linked account') }}</p>
                            @endif
                        </dd>
                    </div>
                    @if ($ticket && $eTicketUrl)
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('E-ticket') }}</dt>
                            <dd class="mt-1 space-y-2 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                                <p class="font-mono text-xs break-all text-sky-700 dark:text-sky-300">
                                    <a href="{{ $eTicketUrl }}" target="_blank" rel="noopener noreferrer" class="hover:underline">{{ $eTicketUrl }}</a>
                                </p>
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:button variant="outline" size="sm" href="{{ $eTicketUrl }}" target="_blank" icon="arrow-top-right-on-square">
                                        {{ __('Open e-ticket') }}
                                    </flux:button>
                                    @if ($canResendEticketWhatsapp)
                                        <form action="{{ route('events.registrations.resend-ticket-whatsapp', [$event, $registration]) }}" method="post" class="inline" onsubmit="return confirm({{ json_encode(__('Send the e-ticket WhatsApp message again? It uses the same template as the first notification.')) }});">
                                            @csrf
                                            <flux:button type="submit" variant="outline" size="sm" icon="chat-bubble-left-right">
                                                {{ __('Resend e-ticket via WhatsApp') }}
                                            </flux:button>
                                        </form>
                                    @endif
                                </div>
                                @if ($canUpdate && ! $rider->user?->whatsapp)
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Save a WhatsApp number on the rider account to resend the e-ticket message.') }}</p>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>
                @if ($canUpdate && $rider->user)
                    <flux:modal name="edit-rider-whatsapp" focusable class="max-w-md" dismissible>
                        <form method="post" action="{{ route('events.registrations.update-rider-user-whatsapp', [$event, $registration]) }}" class="space-y-4 p-2">
                            @csrf
                            <flux:heading size="lg">{{ __('WhatsApp number') }}</flux:heading>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Linked account: :name', ['name' => $rider->user->name]) }}</p>
                            <flux:input
                                name="whatsapp"
                                type="text"
                                :label="__('WhatsApp')"
                                :value="old('whatsapp', $rider->user->whatsapp)"
                                :placeholder="__('e.g. 62812…')"
                            />
                            @error('whatsapp')
                                <p class="text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror
                            <div class="flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                    @if ($errors->has('whatsapp'))
                        <div x-data x-init="$nextTick(() => $dispatch('modal-show', { name: 'edit-rider-whatsapp' }))"></div>
                    @endif
                @endif
                @if ($canUpdate)
                    <flux:modal name="edit-rider-data" focusable class="max-w-lg" dismissible>
                        <form method="post" action="{{ route('events.registrations.update-rider-data', [$event, $registration]) }}" class="max-h-[85vh] space-y-4 overflow-y-auto p-2">
                            @csrf
                            <flux:heading size="lg">{{ __('Edit rider') }}</flux:heading>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Updates the rider profile and teams for this registration. Data must still match this registration’s bracket rules.') }}</p>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:input name="name" type="text" :label="__('Full name')" :value="old('name', $rider->name)" required />
                                <flux:input name="nickname" type="text" :label="__('Nickname')" :value="old('nickname', $rider->nickname)" />
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:input name="pob" type="text" :label="__('Place of birth')" :value="old('pob', $rider->pob)" />
                                <flux:input name="dob" type="date" :label="__('Date of birth')" :value="old('dob', $rider->dob?->format('Y-m-d'))" required />
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <flux:select name="gender" :label="__('Gender')" required>
                                    <option value="boys" @selected(old('gender', $rider->gender) === 'boys')>{{ __('Boys') }}</option>
                                    <option value="girls" @selected(old('gender', $rider->gender) === 'girls')>{{ __('Girls') }}</option>
                                    <option value="other" @selected(old('gender', $rider->gender) === 'other')>{{ __('Other') }}</option>
                                </flux:select>
                                <flux:input name="number_plate" type="text" :label="__('Number plate')" :value="old('number_plate', $rider->number_plate)" />
                            </div>

                            @php
                                $pillboxInitialTeamIds = array_values(array_unique(array_map('intval', (array) old('team_ids', $registration->team_ids ?? []))));
                            @endphp
                            @livewire('team-pillbox-field', [
                                'organizerId' => $event->organizer_id,
                                'initialTeamIds' => $pillboxInitialTeamIds,
                                'fieldLabel' => __('Teams / sponsors'),
                            ])
                            @error('team_ids')
                                <p class="text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror

                            @error('rider_data')
                                <flux:callout variant="danger" class="rounded-lg text-sm">{{ $message }}</flux:callout>
                            @enderror

                            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                    @if ($openEditRiderDataModal)
                        <div x-data x-init="$nextTick(() => $dispatch('modal-show', { name: 'edit-rider-data' }))"></div>
                    @endif
                @endif
                @if ($showStatusActions)
                    <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
                        <div class="flex flex-wrap gap-2">
                            @if ($registration->isApproved())
                                <flux:button variant="primary" color="green" size="sm" icon="check" disabled>
                                    {{ __('Approved') }}
                                </flux:button>
                            @else
                                <form action="{{ route('registrations.update-status', $registration) }}" method="post" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="approved" />
                                    <flux:button variant="primary" color="green" type="submit" size="sm" icon="check">
                                        {{ __('Approve registration') }}
                                    </flux:button>
                                </form>
                                <form action="{{ route('registrations.update-status', $registration) }}" method="post" class="inline-flex flex-col gap-2 items-start">
                                    @csrf
                                    <input type="hidden" name="status" value="rejected" />
                                    <flux:button variant="outline" color="red" type="submit" size="sm" icon="x-mark">
                                        {{ __('Reject') }}
                                    </flux:button>
                                    <textarea name="rejection_reason" rows="2" class="w-full max-w-md rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100" placeholder="{{ __('Optional reason (sent to participant)') }}"></textarea>
                                </form>
                            @endif
                            <form action="{{ route('registrations.update-status', $registration) }}" method="post" class="inline">
                                @csrf
                                <input type="hidden" name="status" value="cancelled" />
                                <flux:button variant="ghost" color="zinc" type="submit" size="sm" icon="no-symbol">
                                    {{ __('Cancel registration') }}
                                </flux:button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
                <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Photo KIA') }}</h2>
                </div>
                <div class="p-4" x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                    @if ($photoKiaUrl)
                        <button type="button" @click="previewOpen = true" class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 overflow-hidden bg-zinc-100 dark:bg-zinc-700/50 hover:opacity-95 transition cursor-pointer text-left">
                            <img src="{{ $photoKiaUrl }}" alt="{{ __('Photo KIA') }}" class="w-full max-h-[320px] object-contain object-top" />
                        </button>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Click image for full preview') }}</p>
                        <div x-show="previewOpen" x-transition.opacity x-cloak
                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4"
                            @click.self="previewOpen = false"
                            role="dialog" aria-modal="true" :aria-hidden="!previewOpen">
                            <img src="{{ $photoKiaUrl }}" alt="{{ __('Photo KIA') }}"
                                class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-2xl"
                                @click.stop />
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 py-12 text-center">
                            <span class="text-zinc-400 dark:text-zinc-500 text-4xl mb-2">🖼️</span>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No photo KIA uploaded') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Data pendaftaran + pembayaran (ringkas) --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
                <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Payment Detail') }}</h2>
                </div>
                <div class="p-4">
                    @if ($payment)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:badge :color="$payBadgeColor" size="sm">{{ $payment->status_label }}</flux:badge>
                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $payment->formatted_amount }}</span>
                        </div>
                        @if ($payment->method === 'manual' && $payment->manual_transfer_amount && ($payment->isPending() || $payment->isSubmitted()))
                            <p class="mt-2 text-xs text-amber-800 dark:text-amber-200">
                                {{ __('Expected transfer amount') }}: <span class="font-mono font-semibold">{{ $payment->formatted_manual_transfer_amount }}</span>
                                @if ($payment->manualUniqueSuffixFormatted())
                                    ({{ __('unique code') }} {{ $payment->manualUniqueSuffixFormatted() }})
                                @endif
                            </p>
                        @endif
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Submitted') }}: {{ $payment->created_at->format('d/m/Y H:i') }}</p>
                        @if ($canUpdate)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @if ($payment->isSuccess())
                                    <flux:button variant="primary" color="green" size="sm" icon="check" disabled>
                                        {{ __('Approved') }}
                                    </flux:button>
                                @elseif (($payment->isPending() || $payment->isSubmitted()) && $transferProofUrl)
                                    <form action="{{ route('payments.approve', $payment) }}" method="post" class="inline">
                                        @csrf
                                        <flux:button variant="primary" color="green" type="submit" size="sm" icon="check">
                                            {{ __('Approve payment') }}
                                        </flux:button>
                                    </form>
                                    <form action="{{ route('payments.reject', $payment) }}" method="post" class="inline">
                                        @csrf
                                        <flux:button variant="outline" color="red" type="submit" size="sm" icon="x-mark">
                                            {{ __('Reject payment') }}
                                        </flux:button>
                                    </form>
                                @endif
                                @if ($canResetPaymentDeadline)
                                    <form action="{{ route('events.registrations.reset-payment-deadline', [$event, $registration]) }}" method="post" class="inline" onsubmit="return confirm({{ json_encode(__('Reset payment deadline from now? If the order was cancelled for expiry, a free slot is still required; the rider may receive a new unique transfer amount.')) }});">
                                        @csrf
                                        <flux:button variant="outline" color="amber" type="submit" size="sm" icon="arrow-path">
                                            {{ __('Reset payment deadline') }}
                                        </flux:button>
                                    </form>
                                @endif
                                @if ($canReopenPayment)
                                    <form action="{{ route('events.registrations.reopen-payment', [$event, $registration]) }}" method="post" class="inline" onsubmit="return confirm({{ json_encode(__('Create a new payment attempt? Quota for this bracket/package will be checked first.')) }});">
                                        @csrf
                                        <flux:button variant="outline" color="sky" type="submit" size="sm" icon="banknotes">
                                            {{ __('Reopen payment') }}
                                        </flux:button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No payment yet') }}</p>

                        @if ($canGeneratePayment)
                            <form action="{{ route('events.registrations.generate-payment', [$event, $registration]) }}" method="post" class="mt-4 space-y-3" onsubmit="return confirm({{ json_encode(__('Create a new payment attempt? Quota for this bracket/package will be checked first.')) }});">
                                @csrf
                                @if ($generatePaymentBothOptions)
                                    <fieldset class="space-y-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 p-3">
                                        <legend class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ __('Payment method') }}</legend>
                                        <label class="flex cursor-pointer items-start gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                                            <input type="radio" name="payment_method" value="manual" class="mt-0.5 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:ring-zinc-400" required @checked(old('payment_method') === 'manual')>
                                            <span>{{ __('Manual transfer (upload proof)') }}</span>
                                        </label>
                                        <label class="flex cursor-pointer items-start gap-2 text-sm text-zinc-800 dark:text-zinc-200">
                                            <input type="radio" name="payment_method" value="qris" class="mt-0.5 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:ring-zinc-400" @checked(old('payment_method') === 'qris')>
                                            <span>{{ __('QRIS (auto confirm)') }}</span>
                                        </label>
                                    </fieldset>
                                    @error('payment_method')
                                        <p class="text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif
                                <flux:button variant="primary" type="submit" size="sm" icon="banknotes">
                                    {{ __('Generate payment') }}
                                </flux:button>
                            </form>
                        @endif

                        @if ($canResetPaymentDeadline)
                            <form action="{{ route('events.registrations.reset-payment-deadline', [$event, $registration]) }}" method="post" class="mt-3 inline" onsubmit="return confirm({{ json_encode(__('Reset payment deadline from now? If the order was cancelled for expiry, a free slot is still required; the rider may receive a new unique transfer amount.')) }});">
                                @csrf
                                <flux:button variant="outline" color="amber" type="submit" size="sm" icon="arrow-path">
                                    {{ __('Reset payment deadline') }}
                                </flux:button>
                            </form>
                        @endif
                        @if ($canReopenPayment)
                            <form action="{{ route('events.registrations.reopen-payment', [$event, $registration]) }}" method="post" class="mt-3 inline" onsubmit="return confirm({{ json_encode(__('Create a new payment attempt? Quota for this bracket/package will be checked first.')) }});">
                                @csrf
                                <flux:button variant="outline" color="sky" type="submit" size="sm" icon="banknotes">
                                    {{ __('Reopen payment') }}
                                </flux:button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
                <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Bukti transfer') }}</h2>
                </div>
                <div class="p-4" x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                    @if ($transferProofUrl)
                        <button type="button" @click="previewOpen = true" class="block w-full rounded-lg border border-zinc-200 dark:border-zinc-600 overflow-hidden bg-zinc-100 dark:bg-zinc-700/50 hover:opacity-95 transition cursor-pointer text-left">
                            <img src="{{ $transferProofUrl }}" alt="{{ __('Bukti transfer') }}" class="w-full max-h-[320px] object-contain object-top" />
                        </button>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Click image for full preview') }}</p>
                        <div x-show="previewOpen" x-transition.opacity x-cloak
                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4"
                            @click.self="previewOpen = false"
                            role="dialog" aria-modal="true" :aria-hidden="!previewOpen">
                            <img src="{{ $transferProofUrl }}" alt="{{ __('Bukti transfer') }}"
                                class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-2xl"
                                @click.stop />
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600 bg-zinc-50 dark:bg-zinc-800/50 py-12 text-center">
                            <span class="text-zinc-400 dark:text-zinc-500 text-4xl mb-2">📄</span>
                            @if ($payment)
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No transfer proof uploaded yet') }}</p>
                                @if ($registration->order && $waSendPaymentUrl)
                                    <a href="{{ $waSendPaymentUrl }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-[#25D366] px-3 py-2 text-sm font-medium text-white hover:bg-[#20BD5A] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
                                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ __('Send payment link via WhatsApp') }}
                                    </a>
                                @endif
                            @else
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No payment record yet') }}</p>
                                @if ($registration->order && $waSendPaymentUrl)
                                    <a href="{{ $waSendPaymentUrl }}" target="_blank" rel="noopener" class="mt-2 inline-flex items-center gap-2 rounded-lg bg-[#25D366] px-3 py-2 text-sm font-medium text-white hover:bg-[#20BD5A] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
                                        <svg class="size-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ __('Send payment link via WhatsApp') }}
                                    </a>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if (($canApproveAll && $transferProofUrl) || ($canUpdate && $nextRegistration))
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-4">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($canApproveAll && $transferProofUrl)
                        <form action="{{ route('registrations.approve-all', $registration) }}" method="post" class="inline">
                            @csrf
                            <flux:button variant="primary" color="green" type="submit" icon="check">
                                {{ __('Approve registration and payment') }}
                            </flux:button>
                        </form>
                    @endif
                    @if ($nextRegistration)
                        <flux:button variant="outline" :href="route('events.registrations.show', [$event, $nextRegistration])" wire:navigate icon="arrow-right">
                            {{ __('Next') }}
                        </flux:button>
                    @else
                        <flux:button variant="outline" disabled>
                            {{ __('No registration needs review.') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
            <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 px-4 py-3">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Activity log') }}</h2>
            </div>
            <div class="p-4">
                <ol class="flex flex-col" role="list">
                    @foreach ($registrationActivityLog as $entry)
                        <li class="relative pb-8 last:pb-0">
                            @unless ($loop->first)
                                <div class="absolute start-[1.125rem] top-0 h-2 w-px -translate-x-1/2 bg-zinc-200 dark:bg-zinc-600" aria-hidden="true"></div>
                            @endunless
                            @unless ($loop->last)
                                <div class="absolute start-[1.125rem] top-5 bottom-0 w-px -translate-x-1/2 bg-zinc-200 dark:bg-zinc-600" aria-hidden="true"></div>
                            @endunless
                            <span class="absolute start-[1.125rem] top-2 z-[1] size-3 -translate-x-1/2 rounded-full border-2 border-white bg-zinc-400 shadow-sm dark:border-zinc-800 dark:bg-zinc-500" aria-hidden="true"></span>
                            <div class="min-w-0 ps-10 pt-0.5 sm:ps-11">
                                <p class="text-sm font-medium leading-snug text-zinc-900 dark:text-zinc-100">{{ $entry['title'] }}</p>
                                <time class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400" datetime="{{ $entry['at']->format('c') }}">
                                    {{ $entry['at']->format('d/m/Y H:i') }}
                                </time>
                                @if (! empty($entry['detail']))
                                    <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ $entry['detail'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
</x-layouts::app>

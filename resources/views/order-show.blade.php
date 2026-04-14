<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Order') . ' #' . $order->id . ' — ' . config('app.name')])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('Order') }} #{{ $order->id }}
                </h1>
                <span
                    @if (!$order->isPaid() && !$order->isConfirmed() && $order->expired_at)
                        data-expires-at="{{ $order->expired_at->format('c') }}"
                        data-time-up="{{ __('Time\'s up') }}"
                        x-data="orderCountdown()"
                        x-init="init()"
                    @endif
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-sm font-medium
                    @if ($order->isPaid() || $order->isCompleted()) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                    @else bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                    @endif">
                    {{ $order->participantCheckoutLabel() }}
                    @if (!$order->isPaid() && !$order->isConfirmed() && $order->expired_at)
                        <span x-text="text"></span>
                    @endif
                </span>
            </div>

            @php
                $reg = $order->registration;
                $event = $reg->event;
                $event->loadMissing('accounts');
                $rider = $reg->rider;
                $payment = $reg->payment;
                $amount = $reg->package ? $reg->package->payableAmount() : 0;
                $whatsapp = $rider->user?->whatsapp ?? '';
                $allowManualPay = $event->allowsManualPayment();
                $allowQrisPay = $event->allowsQrisPayment();
                $paymentCreateBase = array_filter([
                    'order_code' => $order->order_code,
                    'whatsapp' => $whatsapp !== '' ? $whatsapp : null,
                ]);
                if (! empty($freshPayment ?? false)) {
                    $paymentCreateBase['fresh_payment'] = '1';
                }
                $showPaymentProofCountdown = ! $order->isPaid() && ! $order->proof_uploaded && $order->isConfirmed() && $payment && $payment->isPending() && empty($payment->transfer_proof_path) && $payment->expires_at;
                $registrationTeams = filled($reg->team_ids)
                    ? \App\Models\Team::query()
                        ->whereIn('id', array_values(array_filter((array) $reg->team_ids)))
                        ->orderBy('name')
                        ->get()
                    : collect();
                $photoKiaUrl = $rider->getFirstMediaUrl('photo_kia') ?: ($rider->photo_kia ?: null);
                $transferProofCompleted = $order->proof_uploaded || $order->isPaid() || ($payment && $payment->isSuccess());
                $progressTimeline = [
                    ['label' => __('Registration Submitted'), 'checked' => $reg->created_at !== null],
                    ['label' => __('Payment Method Selected'), 'checked' => $payment && filled($payment->method)],
                    ['label' => __('Order Confirmed'), 'checked' => $order->isConfirmed()],
                    ['label' => __('Transfer Proof Uploaded'), 'checked' => $transferProofCompleted],
                    ['label' => __('Registration Approved'), 'checked' => $reg->isApproved()],
                    ['label' => __('Payment Verified'), 'checked' => $order->isPaid()],
                    ['label' => __('Ticket Issued'), 'checked' => $reg->ticket !== null],
                ];
            @endphp
            @if ($showPaymentProofCountdown)
                <div
                    class="mb-6"
                    data-expires-at="{{ $payment->expires_at->format('c') }}"
                    data-time-up="{{ __('Time\'s up') }}"
                    data-upload-within="{{ __('Waiting for payment in') }}"
                    x-data="paymentProofCountdown()"
                    x-init="init()">
                    <flux:callout color="amber" icon="clock">
                        <flux:callout.heading>
                            <span x-text="text"></span>
                        </flux:callout.heading>
                        <flux:callout.text>
                            {{ __('Payment will expire if proof is not uploaded in time.') }}
                        </flux:callout.text>
                    </flux:callout>
                </div>
            @endif

            <div class="space-y-6">
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-100/80 px-4 py-3 dark:bg-zinc-800/80">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Event Detail') }}</h2>
                    </div>
                    <dl class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Event') }}</dt>
                            <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                                <span class="font-medium">{{ $event->title }}</span>
                                @if ($event->location)
                                    <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">{{ $event->location->name }}</span>
                                @endif
                                @if ($event->start_at)
                                    <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">{{ $event->start_at->translatedFormat('l, j F Y') }}</span>
                                @endif
                                @if ($event->location?->google_map)
                                    <a href="{{ $event->location->google_map }}" target="_blank" rel="noopener noreferrer" class="mt-1 inline-block text-sm text-amber-700 underline hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300">
                                        {{ __('Open map') }}
                                    </a>
                                @endif
                            </dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</dt>
                            <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->package?->name ?? '—' }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</dt>
                            <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->bracket->name }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-amber-600 dark:text-amber-400 sm:col-span-2 sm:mt-0">
                                {{ 'Rp ' . number_format((float) $amount, 0, ',', '.') }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-100/80 px-4 py-3 dark:bg-zinc-800/80">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Registration Detail') }}</h2>
                    </div>
                    <dl class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Registered at') }}</dt>
                            <dd class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 sm:col-span-2 sm:mt-0">{{ $reg->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Rider') }}</dt>
                            <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                                {{ $rider->name }}@if ($rider->nickname) ({{ $rider->nickname }})@endif
                                @if ($rider->dob || $rider->gender)
                                    <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">
                                        @if ($rider->dob){{ $rider->dob->format('d/m/Y') }}@endif
                                        @if ($rider->dob && ($rider->gender_label ?? $rider->gender)) · @endif
                                        @if ($rider->gender_label ?? $rider->gender){{ $rider->gender_label ?? $rider->gender }}@endif
                                    </span>
                                @endif
                                @if ($rider->pob)
                                    <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">{{ __('Place of birth') }}: {{ $rider->pob }}</span>
                                @endif
                                @if ($rider->user?->whatsapp)
                                    <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">{{ __('WhatsApp') }}: {{ $rider->user->whatsapp }}</span>
                                @endif
                                @if ($rider->user?->email)
                                    <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">{{ __('Email') }}: {{ $rider->user->email }}</span>
                                @endif
                                @if ($rider->user?->phone)
                                    <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">{{ __('Phone') }}: {{ $rider->user->phone }}</span>
                                @endif
                            </dd>
                        </div>
                        @if ($reg->number_plate || $rider->number_plate)
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->number_plate ?: $rider->number_plate }}</dd>
                            </div>
                        @endif
                        @if ($registrationTeams->isNotEmpty())
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Teams') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $registrationTeams->pluck('name')->join(', ') }}</dd>
                            </div>
                        @endif
                        @if ($reg->jersey_size)
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Jersey size') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->jersey_size }}</dd>
                            </div>
                        @endif
                        @if ($reg->ticket)
                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Ticket code') }}</dt>
                                <dd class="mt-1 font-mono text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->ticket->ticket_code }}</dd>
                            </div>
                        @endif
                        @if ($photoKiaUrl)
                        <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4" x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                        <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Photo KIA') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                            <button type="button" @click="previewOpen = true" class="inline-flex items-center rounded-lg border border-zinc-200 bg-zinc-100 px-3 py-2 text-sm text-zinc-700 transition hover:opacity-95 dark:border-zinc-600 dark:bg-zinc-700/50 dark:text-zinc-200">
                                {{ __('Preview image') }}
                            </button>
                            <div
                                x-show="previewOpen"
                                x-transition.opacity
                                x-cloak
                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4"
                                @click.self="previewOpen = false"
                                role="dialog"
                                aria-modal="true"
                                :aria-hidden="!previewOpen">
                                <img
                                    src="{{ $photoKiaUrl }}"
                                    alt="{{ __('Photo KIA') }}"
                                    class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl"
                                    @click.stop />
                            </div>
                        </dd>
                        </div>
                    @endif
                    </dl>
                    
                </div>

                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                    <div class="border-b border-zinc-200 dark:border-zinc-700 bg-zinc-100/80 px-4 py-3 dark:bg-zinc-800/80">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Progress') }}</h2>
                    </div>
                    <div class="p-4">
                        <ol class="flex flex-col" role="list">
                            @foreach ($progressTimeline as $item)
                                <li class="relative pb-5 last:pb-0">
                                    @unless ($loop->last)
                                        <div class="absolute start-[0.625rem] top-5 bottom-0 w-px -translate-x-1/2 {{ $item['checked'] ? 'bg-green-300 dark:bg-green-700/70' : 'bg-zinc-300 dark:bg-zinc-700' }}" aria-hidden="true"></div>
                                    @endunless
                                    <span class="absolute start-[0.625rem] top-1 z-[1] size-3 -translate-x-1/2 rounded-full border-2 border-white shadow-sm dark:border-zinc-800 {{ $item['checked'] ? 'bg-green-500' : 'bg-zinc-400 dark:bg-zinc-500' }}" aria-hidden="true"></span>
                                    <div class="min-w-0 ps-6">
                                        <p class="text-sm font-medium leading-snug {{ $item['checked'] ? 'text-green-700 dark:text-green-300' : 'text-zinc-700 dark:text-zinc-200' }}">
                                            {{ $loop->iteration }}. {{ $item['label'] }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                @if ($order->isPaid())
                    @if ($order->registration->ticket)
                        <div class="flex flex-wrap gap-3">
                            <flux:button href="{{ route('orders.ticket', $order) }}" variant="primary">
                                {{ __('Lihat E-Ticket') }}
                            </flux:button>
                            <flux:button href="{{ route('orders.index') }}" variant="ghost">
                                {{ __('Pesanan saya') }}
                            </flux:button>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Your e-ticket will appear here after your registration is approved by the organizer.') }}
                        </p>
                    @endif
                @else
                    <div class="flex flex-col gap-4">
                        @if (!$order->isExpired())
                            @if ($allowManualPay && $allowQrisPay)
                                @php
                                    $payUrlQris = route('payment.create', array_merge($paymentCreateBase, ['payment_method' => 'qris']));
                                    $payUrlManual = route('payment.create', array_merge($paymentCreateBase, ['payment_method' => 'manual']));
                                @endphp
                                <div
                                    class="space-y-4"
                                    x-data="{
                                        method: 'qris',
                                        urls: { qris: @js($payUrlQris), manual: @js($payUrlManual) },
                                        payHref() { return this.urls[this.method]; },
                                    }"
                                >
                                    <flux:radio.group
                                        x-model="method"
                                        :label="__('Choose how you want to pay')"
                                        variant="cards"
                                        class="w-full flex-col gap-3"
                                    >
                                        <flux:radio
                                            value="qris"
                                            icon="bolt"
                                            :label="__('QRIS / Moota (automatic)')"
                                            :description="__('Payment is confirmed automatically when we receive it.')"
                                        />
                                        <flux:radio
                                            value="manual"
                                            icon="photo"
                                            :label="__('Manual bank transfer')"
                                            :description="__('Transfer to the organizer account, then upload proof of payment.')"
                                        />
                                    </flux:radio.group>
                                    <flux:button
                                        type="button"
                                        variant="primary"
                                        class="w-full justify-center sm:w-auto"
                                        x-on:click="window.location.assign(payHref())"
                                    >
                                        {{ __('Confirm & Pay') }}
                                    </flux:button>
                                </div>
                            @elseif ($allowQrisPay)
                                <flux:button
                                    href="{{ route('payment.create', array_merge($paymentCreateBase, ['payment_method' => 'qris'])) }}"
                                    variant="primary"
                                >
                                    {{ __('Confirm & Pay') }}
                                </flux:button>
                            @elseif ($allowManualPay)
                                <flux:button
                                    href="{{ route('payment.create', array_merge($paymentCreateBase, ['payment_method' => 'manual'])) }}"
                                    variant="primary"
                                >
                                    {{ __('Confirm & Pay') }}
                                </flux:button>
                            @else
                                <flux:callout color="amber">
                                    <flux:callout.text>
                                        {{ __('No payment method is set up for this event. Please contact the organizer.') }}
                                    </flux:callout.text>
                                </flux:callout>
                            @endif
                        @endif
                        <div class="flex flex-wrap gap-3">
                            <flux:button href="{{ route('orders.index') }}" variant="ghost">
                                {{ __('Back to my orders') }}
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>

    @include('partials.footer')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderCountdown', () => ({
                expiresAt: null,
                text: '—',
                init() {
                    const el = this.$el;
                    this.expiresAt = new Date(el.dataset.expiresAt);
                    const timeUpLabel = el.dataset.timeUp || "Time's up";
                    const confirmWithinLabel = el.dataset.confirmWithin || "";
                    const update = () => {
                        const sec = Math.max(0, Math.floor((this.expiresAt - new Date()) / 1000));
                        if (sec <= 0) {
                            this.text = timeUpLabel;
                            return;
                        }
                        const m = Math.floor(sec / 60);
                        const s = sec % 60;
                        this.text = confirmWithinLabel + ' ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    };
                    update();
                    setInterval(update, 1000);
                }
            }));
            Alpine.data('paymentProofCountdown', () => ({
                expiresAt: null,
                text: '—',
                init() {
                    const el = this.$el;
                    this.expiresAt = new Date(el.dataset.expiresAt);
                    const timeUpLabel = el.dataset.timeUp || "Time's up";
                    const uploadWithinLabel = el.dataset.uploadWithin || "Waiting for payment in";
                    const update = () => {
                        const sec = Math.max(0, Math.floor((this.expiresAt - new Date()) / 1000));
                        if (sec <= 0) {
                            this.text = timeUpLabel;
                            return;
                        }
                        const m = Math.floor(sec / 60);
                        const s = sec % 60;
                        this.text = uploadWithinLabel + ' ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    };
                    update();
                    setInterval(update, 1000);
                }
            }));
        });
    </script>
    @fluxScripts
</body>
</html>

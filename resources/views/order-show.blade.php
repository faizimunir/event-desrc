<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Order') . ' #' . $order->id . ' — ' . config('app.name')])
</head>
<body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
    @include('partials.navbar')

    <main class="mx-auto max-w-2xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            @if (session('status'))
                <div class="border-b border-emerald-100/80 bg-emerald-50/90 px-6 py-4 dark:border-emerald-900/40 dark:bg-emerald-950/35 sm:px-8">
                    <div class="flex gap-3 text-sm text-emerald-900 dark:text-emerald-100">
                        <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ session('status') }}</span>
                    </div>
                </div>
            @endif
            @if (session('error'))
                <div class="border-b border-red-100/80 bg-red-50/90 px-6 py-4 dark:border-red-900/40 dark:bg-red-950/35 sm:px-8">
                    <div class="flex gap-3 text-sm text-red-900 dark:text-red-100">
                        <flux:icon name="exclamation-circle" class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
                        <span>{{ session('error') }}</span>
                    </div>
                </div>
            @endif
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
                $headerOrderCheckoutCountdown = ! $order->isPaid() && ! $order->isConfirmed() && $order->expired_at;
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
            <div class="border-b border-zinc-100 px-6 py-6 dark:border-zinc-800 sm:px-8 sm:py-7">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                        {{ __('Order summary') }}
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                        {{ __('Order') }} #{{ $order->id }}
                    </h1>
                    @if ($order->order_code)
                        <p class="mt-2 font-mono text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $order->order_code }}
                        </p>
                    @endif
                    @if ($order->isPaid() || $order->isCompleted())
                        <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset bg-emerald-50 text-emerald-800 ring-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-800/50">
                            <flux:icon name="check-circle" class="size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                            {{ $order->participantCheckoutLabel() }}
                        </span>
                    @elseif ($showPaymentProofCountdown)
                        <span
                            class="mt-3 inline-flex max-w-full flex-wrap items-center gap-x-2 gap-y-0.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-900 ring-amber-200/80 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50"
                            data-expires-at="{{ $payment->expires_at->format('c') }}"
                            data-time-up="{{ __('Time\'s up') }}"
                            data-upload-within="{{ __('Waiting for payment in') }}"
                            x-data="paymentProofCountdown()"
                            x-init="init()">
                            <flux:icon name="clock" class="size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                            <span class="min-w-0 leading-snug" x-text="text"></span>
                        </span>
                    @elseif ($headerOrderCheckoutCountdown)
                        <span
                            class="mt-3 inline-flex max-w-full flex-wrap items-center gap-x-2 gap-y-0.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-900 ring-amber-200/80 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50"
                            data-expires-at="{{ $order->expired_at->format('c') }}"
                            data-time-up="{{ __('Time\'s up') }}"
                            x-data="orderCountdown()"
                            x-init="init()">
                            <span class="inline-flex min-w-0 items-center gap-1.5">
                                <flux:icon name="clock" class="size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                                <span class="min-w-0">{{ $order->participantCheckoutLabel() }}</span>
                            </span>
                            <span class="font-mono text-[11px] font-semibold tabular-nums text-amber-950 dark:text-amber-100 sm:text-xs" x-text="text"></span>
                        </span>
                    @else
                        <span class="mt-3 inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-900 ring-amber-200/80 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50">
                            <flux:icon name="clock" class="size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                            {{ $order->participantCheckoutLabel() }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="px-6 py-6 sm:px-8 sm:py-8">
                <div class="space-y-8">
                    <section class="rounded-2xl bg-zinc-50 ring-1 ring-zinc-200/80 dark:bg-zinc-800/40 dark:ring-zinc-700/80">
                        <h2 class="p-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Order Detail') }}</h2>
                        <dl>
                            <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-700/80 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Registered at') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-600 dark:text-zinc-400 sm:col-span-2 sm:mt-0">{{ $reg->created_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-700/80 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Event') }}</dt>
                                <dd class="mt-1 min-w-0 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                                    <span class="font-medium">{{ $event->title }}</span>
                                    @if ($event->location)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ $event->location->name }}</span>
                                    @endif
                                    @if ($event->start_at)
                                        <span class="mt-0.5 block text-zinc-600 dark:text-zinc-400">{{ $event->start_at->translatedFormat('l, j F Y') }}</span>
                                    @endif
                                    @if ($event->location?->google_map)
                                        <a href="{{ $event->location->google_map }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-flex text-sm font-medium text-zinc-700 underline decoration-zinc-300 underline-offset-4 transition hover:text-zinc-900 dark:text-zinc-300 dark:decoration-zinc-600 dark:hover:text-zinc-100">
                                            {{ __('Open map') }}
                                        </a>
                                    @endif
                                </dd>
                            </div>
                            <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-700/80 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Rider') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">
                                    {{ $rider->name }}@if ($rider->nickname) ({{ $rider->nickname }})@endif
                                    @if ($rider->dob || $rider->gender)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">
                                            @if ($rider->dob){{ $rider->dob->format('d/m/Y') }}@endif
                                            @if ($rider->dob && ($rider->gender_label ?? $rider->gender)) · @endif
                                            @if ($rider->gender_label ?? $rider->gender){{ $rider->gender_label ?? $rider->gender }}@endif
                                        </span>
                                    @endif
                                    @if ($rider->pob)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ __('Place of birth') }}: {{ $rider->pob }}</span>
                                    @endif
                                    @if ($reg->number_plate || $rider->number_plate)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ __('Number plate') }}: {{ $reg->number_plate ?: $rider->number_plate }}</span>
                                    @endif
                                    @if ($registrationTeams->isNotEmpty())
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ __('Teams') }}: {{ $registrationTeams->pluck('name')->join(', ') }}</span>
                                    @endif
                                    @if ($photoKiaUrl)
                                        <div class="mt-1" x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                                            <span class="block text-zinc-600 dark:text-zinc-400">
                                                {{ __('Photo KIA') }}:
                                                <button
                                                    type="button"
                                                    @click="previewOpen = true"
                                                    class="cursor-pointer text-left text-zinc-700 underline decoration-zinc-300 underline-offset-4 transition hover:text-zinc-900 dark:text-zinc-300 dark:decoration-zinc-600 dark:hover:text-zinc-100"
                                                >
                                                    {{ __('Preview image') }}
                                                </button>
                                            </span>
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
                                                    class="max-h-[90vh] max-w-full rounded-2xl object-contain shadow-2xl ring-1 ring-white/10"
                                                    @click.stop />
                                            </div>
                                        </div>
                                    @endif
                                    @if ($reg->jersey_size)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ __('Jersey size') }}: {{ $reg->jersey_size }}</span>
                                    @endif
                                    @if ($rider->user?->whatsapp)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ __('WhatsApp') }}: {{ $rider->user->whatsapp }}</span>
                                    @endif
                                    @if ($rider->user?->email)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ __('Email') }}: {{ $rider->user->email }}</span>
                                    @endif
                                    @if ($rider->user?->phone)
                                        <span class="mt-1 block text-zinc-600 dark:text-zinc-400">{{ __('Phone') }}: {{ $rider->user->phone }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-700/80 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->bracket->name }}</dd>
                            </div>
                            <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-700/80 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</dt>
                                <dd class="mt-1 text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->package?->name ?? '—' }}</dd>
                            </div>
                            <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-700/80 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</dt>
                                <dd class="mt-1 text-xl font-semibold tabular-nums tracking-tight text-zinc-900 dark:text-zinc-50 sm:col-span-2 sm:mt-0">
                                    {{ 'Rp ' . number_format((float) $amount, 0, ',', '.') }}
                                </dd>
                            </div>
                            @if ($reg->ticket)
                                <div class="border-t border-zinc-200/80 p-3 dark:border-zinc-700/80 sm:grid sm:grid-cols-3 sm:gap-4">
                                    <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Ticket code') }}</dt>
                                    <dd class="mt-1 font-mono text-sm text-zinc-900 dark:text-zinc-100 sm:col-span-2 sm:mt-0">{{ $reg->ticket->ticket_code }}</dd>
                                </div>
                            @endif
                        </dl>
                    </section>

                    <section class="rounded-2xl p-3 ring-1 ring-zinc-200/90 dark:ring-zinc-700/90">
                        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Progress') }}</h2>
                        <ol class="relative mt-6 flex flex-col" role="list">
                            @foreach ($progressTimeline as $item)
                                <li class="relative pb-5 last:pb-0">
                                    @unless ($loop->last)
                                        <div class="absolute start-[0.625rem] top-5 bottom-0 w-px -translate-x-1/2 {{ $item['checked'] ? 'bg-emerald-300/90 dark:bg-emerald-700/60' : 'bg-zinc-200 dark:bg-zinc-700' }}" aria-hidden="true"></div>
                                    @endunless
                                    <span class="absolute start-[0.625rem] top-1 z-[1] size-3 -translate-x-1/2 rounded-full border-2 border-white shadow-sm dark:border-zinc-900 {{ $item['checked'] ? 'bg-emerald-500' : 'bg-zinc-400 dark:bg-zinc-500' }}" aria-hidden="true"></span>
                                    <div class="min-w-0 ps-6">
                                        <p class="text-sm font-medium leading-snug {{ $item['checked'] ? 'text-emerald-800 dark:text-emerald-300' : 'text-zinc-500 dark:text-zinc-400' }}">
                                            {{ $loop->iteration }}. {{ $item['label'] }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    @if ($order->isPaid())
                        @if ($order->registration->ticket)
                            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                                <flux:button href="{{ route('orders.ticket', $order) }}" variant="primary" class="w-full justify-center sm:w-auto">
                                    {{ __('Lihat E-Ticket') }}
                                </flux:button>
                                <flux:button href="{{ route('orders.index') }}" variant="ghost" class="w-full justify-center sm:w-auto">
                                    {{ __('Pesanan saya') }}
                                </flux:button>
                            </div>
                        @else
                            <p class="rounded-2xl bg-zinc-50 px-4 py-3 text-sm leading-relaxed text-zinc-600 ring-1 ring-zinc-200/80 dark:bg-zinc-800/40 dark:text-zinc-400 dark:ring-zinc-700/80">
                                {{ __('Your e-ticket will appear here after your registration is approved by the organizer.') }}
                            </p>
                        @endif
                    @else
                        <div class="flex flex-col gap-6">
                            @if (!$order->isExpired())
                                @if ($allowManualPay || $allowQrisPay)
                                    @php
                                        $payUrlQris = route('payment.create', array_merge($paymentCreateBase, ['payment_method' => 'qris']));
                                        $payUrlManual = route('payment.create', array_merge($paymentCreateBase, ['payment_method' => 'manual']));
                                        $defaultPayMethod = $allowQrisPay ? 'qris' : 'manual';
                                    @endphp
                                    <div
                                        class="space-y-5"
                                        x-data="{
                                            method: @js($defaultPayMethod),
                                            urls: { @if ($allowQrisPay) qris: @js($payUrlQris), @endif @if ($allowManualPay) manual: @js($payUrlManual), @endif },
                                            payHref() { return this.urls[this.method]; },
                                        }"
                                    >
                                        <flux:radio.group
                                            x-model="method"
                                            :label="__('Payment Method')"
                                            variant="cards"
                                            class="w-full flex-col gap-3"
                                        >
                                            @if ($allowQrisPay)
                                                <flux:radio
                                                    value="qris"
                                                    icon="bolt"
                                                    :label="__('QRIS')"
                                                    :description="__('Scan QRIS and pay the exact amount shown on the payment page.')"
                                                />
                                            @endif
                                            @if ($allowManualPay)
                                                <flux:radio
                                                    value="manual"
                                                    icon="photo"
                                                    :label="__('Manual bank transfer')"
                                                    :description="__('Transfer to the organizer account, then upload proof of payment.')"
                                                />
                                            @endif
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
                                @else
                                    <div class="flex gap-3 rounded-2xl bg-amber-50 px-4 py-4 text-sm text-amber-950 ring-1 ring-amber-200/70 dark:bg-amber-950/30 dark:text-amber-100 dark:ring-amber-800/50">
                                        <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                                        <span>{{ __('No payment method is set up for this event. Please contact the organizer.') }}</span>
                                    </div>
                                @endif
                            @endif
                            <div>
                                <flux:button href="{{ route('orders.index') }}" variant="ghost" size="sm">
                                    {{ __('Back to my orders') }}
                                </flux:button>
                            </div>
                        </div>
                    @endif
                </div>
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
                    const update = () => {
                        const sec = Math.max(0, Math.floor((this.expiresAt - new Date()) / 1000));
                        if (sec <= 0) {
                            this.text = timeUpLabel;
                            return;
                        }
                        const m = Math.floor(sec / 60);
                        const s = sec % 60;
                        this.text = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
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

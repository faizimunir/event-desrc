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
                <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium
                    @if ($order->isPaid() || $order->isCompleted()) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                    @else bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                    @endif">
                    {{ $order->status_label }}
                </span>
            </div>

            @if (!$order->isPaid() && !$order->isConfirmed())
                @if ($order->expired_at)
                    <div
                        class="mb-6"
                        data-expires-at="{{ $order->expired_at->format('c') }}"
                        data-time-up="{{ __('Time\'s up') }}"
                        data-confirm-within="{{ __('Confirm order within') }}"
                        x-data="orderCountdown()"
                        x-init="init()">
                        <flux:callout color="amber" icon="clock">
                            <flux:callout.heading>
                                <span x-text="text"></span>
                            </flux:callout.heading>
                            <flux:callout.text>
                                {{ __('Order will expire automatically if not confirmed in time.') }}
                            </flux:callout.text>
                        </flux:callout>
                    </div>
                @else
                    <flux:callout color="amber" icon="clock" class="mb-6">
                        <flux:callout.heading>
                            {{ __('Confirm order within :minutes minutes.', ['minutes' => \App\Models\Order::ORDER_CONFIRMATION_DEADLINE_MINUTES]) }}
                        </flux:callout.heading>
                        <flux:callout.text>
                            {{ __('Order will expire automatically if not confirmed in time.') }}
                        </flux:callout.text>
                    </flux:callout>
                @endif
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
                $showPaymentProofCountdown = !$order->isPaid() && !$order->proof_uploaded && $order->isConfirmed() && $payment && $payment->isPending() && $payment->expires_at;
            @endphp
            @if ($showPaymentProofCountdown)
                <div
                    class="mb-6"
                    data-expires-at="{{ $payment->expires_at->format('c') }}"
                    data-time-up="{{ __('Time\'s up') }}"
                    data-upload-within="{{ __('Upload proof within') }}"
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
                @if (!$order->isExpired())
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Registration') }}
                        </p>
                        <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $event->title }}</p>
                        @if ($event->location)
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $event->location->name }}</p>
                        @endif
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $rider->name }}
                            @if ($rider->nickname)
                                ({{ $rider->nickname }})
                            @endif
                            · {{ $reg->bracket->name }}
                            @if ($reg->package)
                                · {{ $reg->package->name }}
                            @endif
                        </p>
                        <p class="mt-2 text-lg font-semibold text-amber-600 dark:text-amber-400">
                            {{ 'Rp ' . number_format((float) $amount, 0, ',', '.') }}
                        </p>
                    </div>
                @endif

                @if ($order->isPaid())
                    <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                        {{ __('Your payment has been verified.') }}
                    </div>
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
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('You will confirm the order on the next page and complete payment there.') }}
                                    </p>
                                    <flux:button
                                        type="button"
                                        variant="primary"
                                        class="w-full justify-center sm:w-auto"
                                        x-on:click="window.location.assign(payHref())"
                                    >
                                        {{ __('Continue to payment') }}
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
                    const confirmWithinLabel = el.dataset.confirmWithin || "Confirm order within";
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
                    const uploadWithinLabel = el.dataset.uploadWithin || "Upload proof within";
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

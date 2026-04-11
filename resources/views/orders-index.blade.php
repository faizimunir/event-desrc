<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('My orders') . ' — ' . config('app.name')])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <main class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('My orders') }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($showAll ?? false)
                    {{ __('Your orders and payment status.') }}
                @else
                    {{ __('Pending payment only (matches the cart icon).') }}
                @endif
            </p>
            <p class="mt-2 text-sm">
                @if ($showAll ?? false)
                    <a href="{{ route('orders.index') }}" class="text-amber-700 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300">
                        {{ __('Show only pending payment') }}
                    </a>
                @else
                    <a href="{{ route('orders.index', ['all' => true]) }}" class="text-zinc-600 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                        {{ __('Show all orders (including paid)') }}
                    </a>
                @endif
            </p>
        </div>

        @if ($orders->isEmpty())
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-8 text-center">
                <flux:icon name="shopping-bag" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />
                @if (! ($showAll ?? false))
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                        {{ __('No orders are awaiting payment.') }}
                    </p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">
                        {{ __('Paid or confirmed orders may appear in the full list.') }}
                    </p>
                    <flux:button href="{{ route('orders.index', ['all' => true]) }}" variant="primary" class="mt-6">
                        {{ __('Show all orders') }}
                    </flux:button>
                @else
                    <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                        {{ __('You have no orders yet.') }}
                    </p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">
                        {{ __('Register for an event to see your orders here.') }}
                    </p>
                    <flux:button href="{{ route('home') }}#events" variant="primary" class="mt-6">
                        {{ __('Browse events') }}
                    </flux:button>
                @endif
            </div>
        @else
            <ul class="space-y-4">
                @foreach ($orders as $order)
                    @php
                        $reg = $order->registration;
                        $event = $reg->event;
                        $rider = $reg->rider;
                        $amount = $reg->package ? $reg->package->payableAmount() : 0;
                    @endphp
                    <li>
                        <a href="{{ route('orders.show', $order) }}" class="block rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-5 shadow-sm transition hover:border-zinc-300 hover:shadow-md dark:hover:border-zinc-600">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $event->title }}</p>
                                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $rider->name }} · {{ $reg->bracket->name }}
                                        @if ($reg->package)
                                            · {{ $reg->package->name }}
                                        @endif
                                    </p>
                                    @php
                                        $orderPaid = $order->isPaid();
                                        $showConfirmCountdown = !$order->isPaid() && !$order->isConfirmed() && $order->expired_at;
                                        $payment = $reg->payment;
                                        $showProofCountdown = !$order->isPaid() && !$order->proof_uploaded && $order->isConfirmed() && $payment && $payment->isPending() && $payment->expires_at;
                                    @endphp
                                    <p class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if ($orderPaid) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @else bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                        @endif"
                                        @if ($showProofCountdown)
                                        data-expires-at="{{ $payment->expires_at->format('c') }}"
                                        data-time-up="{{ __('Expired') }}"
                                        data-upload-within="{{ __('Upload proof within') }}"
                                        x-data="paymentProofCountdown()"
                                        x-init="init()"
                                        x-text="text"
                                        @elseif ($showConfirmCountdown)
                                        x-data="{
                                            expiresAt: new Date('{{ $order->expired_at->format('c') }}'),
                                            text: '—',
                                            init() {
                                                const update = () => {
                                                    const sec = Math.max(0, Math.floor((this.expiresAt - new Date()) / 1000));
                                                    if (sec <= 0) { this.text = '{{ __('Expired') }}'; return; }
                                                    const m = Math.floor(sec / 60), s = sec % 60;
                                                    this.text = '{{ __('Confirm order within') }} ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                                };
                                                update();
                                                setInterval(update, 1000);
                                            }
                                        }" x-init="init()" x-text="text"
                                        @endif>
                                        @if ($orderPaid)
                                            {{ __('Paid') }}
                                        @elseif (!$showConfirmCountdown && !$showProofCountdown)
                                            {{ $order->status_label }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-lg font-semibold text-amber-600 dark:text-amber-400">
                                        {{ 'Rp ' . number_format((float) $amount, 0, ',', '.') }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        #{{ $order->id }} · {{ $reg->created_at->format('d M Y') }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>

            @if ($orders->hasPages())
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif

        <p class="mt-8 text-center">
            <a href="{{ route('home') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                {{ __('Back to home') }}
            </a>
        </p>
    </main>

    @include('partials.footer')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentProofCountdown', () => ({
                expiresAt: null,
                text: '—',
                init() {
                    const el = this.$el;
                    this.expiresAt = new Date(el.dataset.expiresAt);
                    const timeUpLabel = el.dataset.timeUp || "Expired";
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

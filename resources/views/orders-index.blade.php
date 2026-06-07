@extends('layouts.bento-public')

@section('title')
    {{ __('My orders') }}
@endsection

@section('content')
    <div class="bento-card bento-page-header">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
            <flux:icon name="shopping-bag" variant="mini" class="size-3.5" />
            {{ __('Orders') }}
        </span>

        <flux:heading size="lg" class="mt-3">{{ __('My orders') }}</flux:heading>
        <flux:subheading class="mt-1.5">
            @if ($showAll ?? false)
                {{ __('Your orders and payment status.') }}
            @else
                {{ __('Pending payment only (matches the cart icon).') }}
            @endif
        </flux:subheading>

        <p class="mt-3 text-sm">
            @if ($showAll ?? false)
                <a href="{{ route('orders.index') }}" class="font-medium text-amber-700 transition hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300">
                    {{ __('Show only pending payment') }}
                </a>
            @else
                <a href="{{ route('orders.index', ['all' => true]) }}" class="font-medium text-zinc-600 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                    {{ __('Show all orders (including paid)') }}
                </a>
            @endif
        </p>

        <div class="mt-8">
            @if ($orders->isEmpty())
                <div class="bento-empty-state">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="shopping-bag" class="size-7 text-zinc-400 dark:text-zinc-500" />
                    </div>
                    @if (! ($showAll ?? false))
                        <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                            {{ __('No orders are awaiting payment.') }}
                        </p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Paid or confirmed orders may appear in the full list.') }}
                        </p>
                        <flux:button href="{{ route('orders.index', ['all' => true]) }}" variant="primary" class="mt-6 !rounded-xl">
                            {{ __('Show all orders') }}
                        </flux:button>
                    @else
                        <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                            {{ __('You have no orders yet.') }}
                        </p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Register for an event to see your orders here.') }}
                        </p>
                        <flux:button href="{{ route('home') }}#events" variant="primary" class="mt-6 !rounded-xl">
                            {{ __('Browse events') }}
                        </flux:button>
                    @endif
                </div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach ($orders as $order)
                        @php
                            $reg = $order->registration;
                            $event = $reg->event;
                            $rider = $reg->rider;
                            $amount = $reg->package ? $reg->package->payableAmount() : 0;
                            $orderPaid = $order->isPaid();
                            $showConfirmCountdown = ! $order->isPaid() && ! $order->isConfirmed() && $order->expired_at;
                            $payment = $reg->payment;
                            $showProofCountdown = ! $order->isPaid() && ! $order->proof_uploaded && $order->isConfirmed() && $payment && $payment->isPending() && empty($payment->transfer_proof_path) && $payment->expires_at;
                            $flowLabel = $order->participantCheckoutLabel();
                        @endphp
                        <a href="{{ route('orders.show', $order) }}" wire:navigate class="order-list-item group">
                            <div class="order-list-item__icon">
                                <flux:icon name="shopping-bag" variant="outline" class="size-5" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-zinc-900 transition group-hover:text-orange-600 dark:text-white dark:group-hover:text-orange-400">
                                    {{ $event->title }}
                                </h3>
                                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $rider->name }} · {{ $reg->bracket->name }}
                                    @if ($reg->package)
                                        · {{ $reg->package->name }}
                                    @endif
                                </p>
                                <p
                                    class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if ($orderPaid) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @else bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                        @endif"
                                    @if ($showProofCountdown)
                                        data-expires-at="{{ $payment->expires_at->format('c') }}"
                                        data-time-up="{{ __('Expired') }}"
                                        data-upload-within="{{ __('Waiting for payment in') }}"
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
                                                    this.text = '{{ __('Confirm your order in') }} ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                                                };
                                                update();
                                                setInterval(update, 1000);
                                            }
                                        }"
                                        x-init="init()"
                                        x-text="text"
                                    @endif
                                >
                                    @if ($orderPaid)
                                        {{ $flowLabel }}
                                    @elseif (! $showConfirmCountdown && ! $showProofCountdown)
                                        {{ $flowLabel }}
                                    @endif
                                </p>
                            </div>

                            <div class="hidden shrink-0 text-right sm:block">
                                <p class="text-base font-semibold tabular-nums text-amber-600 dark:text-amber-400">
                                    {{ 'Rp ' . number_format((float) $amount, 0, ',', '.') }}
                                </p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    #{{ $order->id }} · {{ $reg->created_at->format('d M Y') }}
                                </p>
                            </div>

                            <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:group-hover:text-orange-400" />
                        </a>
                    @endforeach
                </div>

                @if ($orders->hasPages())
                    <div class="mt-6">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentProofCountdown', () => ({
                expiresAt: null,
                text: '—',
                init() {
                    const el = this.$el;
                    this.expiresAt = new Date(el.dataset.expiresAt);
                    const timeUpLabel = el.dataset.timeUp || "Expired";
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
@endpush

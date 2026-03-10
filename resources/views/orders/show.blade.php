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
                    @if ($order->isPaid()) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                    @else bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                    @endif">
                    {{ $order->status_label }}
                </span>
            </div>

            @php
                $reg = $order->registration;
                $event = $reg->event;
                $rider = $reg->rider;
                $payment = $reg->payment;
                $amount = $reg->package ? $reg->package->price : 0;
                $whatsapp = $rider->user?->whatsapp ?? '';
            @endphp

            <div class="space-y-6">
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

                @if ($order->isPaid())
                    <div class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-800 dark:text-green-200">
                        {{ __('Your payment has been verified.') }}
                    </div>
                @else
                    <div class="flex flex-wrap gap-3">
                        <flux:button href="{{ route('payment.create', ['order_id' => $order->id]) }}" variant="primary">
                            {{ __('Pay now') }}
                        </flux:button>
                        <flux:button href="{{ route('orders.index') }}" variant="ghost">
                            {{ __('Back to my orders') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        <p class="mt-6 text-center">
            <a href="{{ route('orders.index') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                {{ __('Back to my orders') }}
            </a>
            ·
            <a href="{{ route('home') }}" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                {{ __('Home') }}
            </a>
        </p>
    </main>

    @include('partials.footer')
</body>
</html>

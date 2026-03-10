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
                {{ __('Registrations submitted and waiting for payment.') }}
            </p>
        </div>

        @if ($orders->isEmpty())
            <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-8 text-center">
                <flux:icon name="shopping-bag" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />
                <p class="mt-4 text-zinc-600 dark:text-zinc-400">
                    {{ __('You have no pending orders.') }}
                </p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">
                    {{ __('Register for an event to see your order here.') }}
                </p>
                <flux:button href="{{ route('home') }}#events" variant="primary" class="mt-6">
                    {{ __('Browse events') }}
                </flux:button>
            </div>
        @else
            <ul class="space-y-4">
                @foreach ($orders as $order)
                    @php
                        $reg = $order->registration;
                        $event = $reg->event;
                        $rider = $reg->rider;
                        $amount = $reg->package ? $reg->package->price : 0;
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
                                    <p class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                        @if ($order->isPaid()) bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300
                                        @else bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                        @endif">
                                        {{ $order->status_label }}
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
</body>
</html>

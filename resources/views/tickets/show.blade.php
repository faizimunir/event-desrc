<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('E-Ticket') . ' — ' . $event->title . ' — ' . config('app.name')])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <main class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('E-Ticket') }}
                </h1>
                <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">
                    {{ __('Valid') }}
                </span>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Event') }}
                    </p>
                    <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">{{ $event->title }}</p>
                    @if ($event->location)
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $event->location->name }}</p>
                    @endif
                    @if ($event->start_at)
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                            {{ $event->start_at->translatedFormat('l, j F Y') }}
                        </p>
                    @endif
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Participant') }}
                    </p>
                    <p class="mt-1 font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $rider->name }}
                        @if ($rider->nickname)
                            <span class="text-zinc-600 dark:text-zinc-400">({{ $rider->nickname }})</span>
                        @endif
                    </p>
                    <dl class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reg->bracket->name }}</dd>
                        </div>
                        @if ($reg->package)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reg->package->name }}</dd>
                            </div>
                        @endif
                        @if ($reg->number_plate ?? $rider->number_plate)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reg->number_plate ?: $rider->number_plate }}</dd>
                            </div>
                        @endif
                        @if ($reg->jersey_size)
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Jersey size') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reg->jersey_size }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="flex flex-col items-center rounded-xl border-2 border-dashed border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800/80 p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Scan at entrance') }}
                    </p>
                    <img
                        src="{{ route('tickets.qr', $ticket) }}"
                        alt="{{ __('QR Code') }}"
                        class="mt-3 h-64 w-64 rounded-lg border border-zinc-200 dark:border-zinc-600"
                    />
                    <p class="mt-2 font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $ticket->ticket_code }}</p>
                </div>

                <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Show this ticket at the event. You can save or share the link.') }}
                </p>
            </div>
        </div>
    </main>

    @include('partials.footer')
    @fluxScripts
</body>
</html>

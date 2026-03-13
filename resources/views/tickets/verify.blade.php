<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Ticket verification') . ' — ' . $event->title])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <main class="mx-auto max-w-xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20 p-6 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/50">
                    <flux:icon name="check-circle" class="h-6 w-6 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Valid ticket') }}
                    </h1>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $event->title }}
                    </p>
                </div>
            </div>

            <dl class="mt-6 space-y-3 border-t border-green-200 dark:border-green-800 pt-4">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Participant') }}</dt>
                    <dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $rider->name }}
                        @if ($rider->nickname)
                            ({{ $rider->nickname }})
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</dt>
                    <dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $reg->bracket->name }}</dd>
                </div>
                @if ($reg->package)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</dt>
                        <dd class="mt-0.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $reg->package->name }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Ticket code') }}</dt>
                    <dd class="mt-0.5 font-mono text-sm text-zinc-900 dark:text-zinc-100">{{ $ticket->ticket_code }}</dd>
                </div>
            </dl>
        </div>
    </main>

    @include('partials.footer')
    @fluxScripts
</body>
</html>

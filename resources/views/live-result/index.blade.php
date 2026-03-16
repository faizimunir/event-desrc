<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Live Result')])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <flux:main container class="py-8">
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item class="text-zinc-900 dark:text-zinc-100">{{ __('Live Result') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading size="lg" class="mb-2">{{ __('Live Result') }}</flux:heading>
        <flux:subheading class="mb-8">{{ __('Lihat hasil live dari berbagai event.') }}</flux:subheading>

        @if($events->isEmpty())
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-12 text-center">
                <flux:icon name="chart-bar" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />
                <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400">{{ __('Belum ada event yang tersedia untuk live result.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($events as $ev)
                    <a href="{{ route('live-result.show', $ev->slug) }}" wire:navigate
                        class="flex gap-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 shadow-sm transition hover:border-orange-300 hover:shadow-md dark:hover:border-orange-600">
                        @if($ev->logoUrl())
                            <div class="flex-shrink-0">
                                <img src="{{ $ev->logoUrl() }}" alt="{{ $ev->title }}" class="h-14 w-auto max-w-[100px] object-contain" />
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ev->title }}</h3>
                            @if($ev->start_at)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $ev->start_at->format('d M Y') }}</p>
                            @endif
                            @if($ev->location)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $ev->location->name }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </flux:main>
    @fluxScripts
</body>
</html>

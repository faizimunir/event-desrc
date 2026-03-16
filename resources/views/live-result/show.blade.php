<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => $event->title . ' — ' . __('Live Result')])
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <flux:main container class="py-8">
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('live-result.index') }}" wire:navigate>{{ __('Live Result') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item class="text-zinc-900 dark:text-zinc-100 truncate max-w-[12rem] sm:max-w-none">{{ $event->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 mb-8">
            <div class="flex flex-wrap items-center gap-4 mb-4">
                @if($event->logoUrl())
                    <div class="flex-shrink-0">
                        <img src="{{ $event->logoUrl() }}" alt="{{ $event->title }}" class="h-16 w-auto object-contain max-w-[150px]" />
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <flux:heading size="lg">{{ $event->title }}</flux:heading>
                    @if($event->location)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $event->location->name }}</p>
                    @endif
                </div>
            </div>
        </div>

        <flux:heading size="lg" class="mb-4">{{ __('Hasil Live') }}</flux:heading>

        @include('live-result.partials.content', [
            'event' => $event,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory ?? null,
            'selectedRound' => $selectedRound ?? null,
            'sheetData' => $sheetData ?? null,
        ])
    </flux:main>
    @fluxScripts
</body>
</html>

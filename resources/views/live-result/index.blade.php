@extends('layouts.bento-public')

@section('title')
    {{ __('Live Result') }}
@endsection

@section('content')
    <div class="bento-card bento-page-header">

        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-red-600 dark:bg-red-500/15 dark:text-red-400">
            <span class="relative flex size-1.5">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
            </span>
            {{ __('Realtime') }}
        </span>

        <flux:heading size="lg" class="mt-3">{{ __('Live Result') }}</flux:heading>
        <flux:subheading class="mt-1.5">{{ __('Lihat hasil live dari berbagai event.') }}</flux:subheading>

        <div class="mt-8">
            @if($events->isEmpty())
                <div class="bento-empty-state">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="chart-bar" class="size-7 text-zinc-400 dark:text-zinc-500" />
                    </div>
                    <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada event yang tersedia untuk live result.') }}</p>
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($events as $ev)
                        <a
                            href="{{ route('live-result.show', $ev->slug) }}"
                            wire:navigate
                            class="group flex gap-4 rounded-2xl border border-zinc-200/70 bg-white p-6 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-zinc-300 hover:shadow-lg dark:border-zinc-700/70 dark:bg-zinc-900/60 dark:hover:border-zinc-600"
                        >
                            @if($ev->logoUrl())
                                <div class="shrink-0">
                                    <img src="{{ $ev->logoUrl() }}" alt="{{ $ev->title }}" class="h-14 w-auto max-w-[100px] object-contain" />
                                </div>
                            @endif
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">{{ $ev->title }}</h3>
                                @if($ev->start_at)
                                    <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                        <flux:icon name="calendar-days" class="size-4 shrink-0" />
                                        {{ $ev->start_at->format('d M Y') }}
                                    </p>
                                @endif
                                @if($ev->location)
                                    <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                        <flux:icon name="map-pin" class="size-4 shrink-0" />
                                        {{ $ev->location->name }}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

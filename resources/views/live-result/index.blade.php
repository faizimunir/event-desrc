@extends('layouts.bento-public')

@section('title')
    {{ __('Live Result') }}
@endsection

@section('content')
    <div class="bento-card bento-page-header">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-red-600 dark:bg-red-500/15 dark:text-red-400">
                    <span class="relative flex size-1.5">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                        <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                    </span>
                    {{ __('Realtime') }}
                </span>

                <flux:heading size="lg" class="mt-3">{{ __('Live Result') }}</flux:heading>
                <flux:subheading class="mt-1.5">{{ __('Pilih event untuk melihat hasil live.') }}</flux:subheading>
            </div>
        </div>

        <div class="mt-8">
            @if($events->isEmpty())
                <div class="bento-empty-state">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="chart-bar" class="size-7 text-zinc-400 dark:text-zinc-500" />
                    </div>
                    <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada live result') }}</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cek kembali nanti untuk event yang tersedia.') }}</p>
                </div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($events as $ev)
                        @include('live-result.partials.event-list-item', ['event' => $ev])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

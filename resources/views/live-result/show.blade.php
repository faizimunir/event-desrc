@extends('layouts.bento-public')

@section('title')
    {{ $event->title }} — {{ __('Live Result') }}
@endsection

@section('content')
    <div class="bento-card bento-page-header">
        <flux:breadcrumbs class="mb-4">
            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>{{ __('Home') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item href="{{ route('live-result.index') }}" wire:navigate>{{ __('Live Result') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item class="max-w-[12rem] truncate text-zinc-900 dark:text-zinc-100 sm:max-w-none">{{ $event->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex min-w-0 flex-1 flex-wrap items-start gap-4">
                @if($event->logoUrl())
                    <div class="live-result-list-item__logo">
                        <img src="{{ $event->logoUrl() }}" alt="{{ $event->title }}" class="max-h-10 max-w-[120px] object-contain" />
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-red-600 dark:bg-red-500/15 dark:text-red-400">
                            <span class="relative flex size-1.5">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                                <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                            </span>
                            {{ __('Live') }}
                        </span>
                    </div>

                    <flux:heading size="lg" class="mt-2">{{ $event->title }}</flux:heading>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @if($event->start_at)
                            <div class="event-meta-item !p-2.5">
                                <div class="event-meta-icon !size-8">
                                    <flux:icon name="calendar-days" class="size-3.5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</p>
                                    <p class="mt-0.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $event->start_at->format('d M Y, H:i') }}</p>
                                </div>
                            </div>
                        @endif

                        @if($event->location)
                            <div class="event-meta-item !p-2.5">
                                <div class="event-meta-icon !size-8">
                                    <flux:icon name="map-pin" class="size-3.5" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</p>
                                    <p class="mt-0.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">{{ $event->location->name }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="live-result-status-bar shrink-0">
                <span class="relative flex size-2">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                    <span class="relative inline-flex size-2 rounded-full bg-red-500"></span>
                </span>
                {{ __('Auto refresh') }}
            </div>
        </div>
    </div>

    <div class="bento-card bento-page-body">
        @include('live-result.partials.content', [
            'event' => $event,
            'categories' => $categories,
            'selectedCategory' => $selectedCategory ?? null,
            'selectedRound' => $selectedRound ?? null,
            'sheetData' => $sheetData ?? null,
        ])
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const pingUrl = @json(route('live-result.ping', $event->slug));
            const pollIntervalMs = 10000;

            let etag = null;
            let inFlight = false;

            async function poll() {
                if (inFlight) return;
                inFlight = true;
                try {
                    const headers = {};
                    if (etag) headers['If-None-Match'] = etag;
                    const res = await fetch(pingUrl, { headers, cache: 'no-store' });

                    if (res.status === 304) return;

                    const newEtag = res.headers.get('ETag');
                    const hadEtag = !!etag;
                    if (newEtag) etag = newEtag;

                    if (!res.ok) return;

                    if (hadEtag) {
                        window.location.reload();
                    }
                } catch (e) {
                    // silent: jangan ganggu UX, coba lagi di interval berikutnya
                } finally {
                    inFlight = false;
                }
            }

            setInterval(poll, pollIntervalMs);
        })();
    </script>
@endpush

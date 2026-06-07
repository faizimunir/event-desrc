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

        <div class="flex flex-wrap items-center gap-4">
            @if($event->logoUrl())
                <div class="shrink-0">
                    <img src="{{ $event->logoUrl() }}" alt="{{ $event->title }}" class="h-16 max-w-[150px] w-auto object-contain" />
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <flux:heading size="lg">{{ $event->title }}</flux:heading>
                @if($event->location)
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="map-pin" class="size-4 shrink-0" />
                        {{ $event->location->name }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="bento-card bento-page-body">
        <flux:heading size="lg" class="mb-6">{{ __('Hasil Live') }}</flux:heading>

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

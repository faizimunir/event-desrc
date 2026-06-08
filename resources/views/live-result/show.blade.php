@extends('layouts.bento-public')

@section('title')
    {{ $event->title }} — {{ __('Live Result') }}
@endsection

@section('content')
    <div class="bento-card bento-section-shell live-result-hero">
        <div class="live-result-hero__inner">
            <div class="live-result-hero__top">
                @if($event->logoUrl())
                    <div class="live-result-hero__logo">
                        <img src="{{ $event->logoUrl() }}" alt="{{ $event->title }}" class="max-h-11 max-w-full object-contain sm:max-h-12" />
                    </div>
                @else
                    <div class="live-result-hero__icon">
                        <flux:icon name="radio" variant="outline" class="size-6 sm:size-7" />
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <span class="live-result-hero__badge">
                        <span class="relative flex size-1.5">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                            <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                        </span>
                        {{ __('Live') }}
                    </span>

                    <h1 class="live-result-hero__title">{{ $event->title }}</h1>
                </div>
            </div>

            @if($event->start_at || $event->location)
                @php
                    $metaCount = (int) (bool) $event->start_at + (int) (bool) $event->location;
                @endphp
                <dl @class([
                    'live-result-hero__meta',
                    'live-result-hero__meta--cols-2' => $metaCount > 1,
                ])>
                    @if($event->start_at)
                        <div class="live-result-hero__meta-item">
                            <dt class="live-result-hero__meta-icon" aria-hidden="true">
                                <flux:icon name="calendar-days" class="size-4" />
                            </dt>
                            <dd class="min-w-0 flex-1">
                                <span class="live-result-hero__meta-label">{{ __('Date') }}</span>
                                <p class="live-result-hero__meta-value">{{ $event->start_at->format('d M Y, H:i') }}</p>
                            </dd>
                        </div>
                    @endif

                    @if($event->location)
                        <div class="live-result-hero__meta-item">
                            <dt class="live-result-hero__meta-icon" aria-hidden="true">
                                <flux:icon name="map-pin" class="size-4" />
                            </dt>
                            <dd class="min-w-0 flex-1">
                                <span class="live-result-hero__meta-label">{{ __('Location') }}</span>
                                <p class="live-result-hero__meta-value">{{ $event->location->name }}</p>
                            </dd>
                        </div>
                    @endif
                </dl>
            @endif
        </div>
    </div>

    <div class="bento-card bento-section-shell">
        <livewire:live-result-panel :event="$event" />
    </div>
@endsection

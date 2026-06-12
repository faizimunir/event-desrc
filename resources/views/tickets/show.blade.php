@extends('layouts.bento-public')

@section('title')
    {{ __('E-Ticket') }} — {{ $event->title }}
@endsection

@section('content')
    <div class="mx-auto w-full max-w-2xl">
        <div class="bento-card overflow-hidden">
            <div class="e-ticket-header">
                <div class="min-w-0">
                    <p class="e-ticket-eyebrow">{{ __('Digital ticket') }}</p>
                    <h1 class="e-ticket-heading">{{ __('E-Ticket') }}</h1>
                </div>
                <span class="e-ticket-valid-badge">
                    <flux:icon name="check-circle" class="size-3.5 shrink-0" />
                    {{ __('Valid') }}
                </span>
            </div>

            <div class="e-ticket-body">
                <div class="e-ticket-top">
                    <div class="min-w-0">
                        <p class="e-ticket-eyebrow">{{ __('Event') }}</p>
                        <p class="e-ticket-title">{{ $event->title }}</p>

                        <ul class="e-ticket-meta-list">
                            @if ($event->location)
                                <li class="e-ticket-meta-item">
                                    <span class="e-ticket-meta-icon" aria-hidden="true">
                                        <flux:icon name="map-pin" class="size-3.5" />
                                    </span>
                                    <span>{{ $event->location->name }}</span>
                                </li>
                            @endif
                            @if ($event->start_at)
                                <li class="e-ticket-meta-item">
                                    <span class="e-ticket-meta-icon" aria-hidden="true">
                                        <flux:icon name="calendar-days" class="size-3.5" />
                                    </span>
                                    <span>{{ $event->start_at->translatedFormat('l, j F Y') }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <div class="e-ticket-qr">
                        <p class="e-ticket-eyebrow">{{ __('Scan at entrance') }}</p>
                        <div class="e-ticket-qr__frame">
                            <img
                                src="{{ route('tickets.qr', $ticket) }}"
                                alt="{{ __('QR Code') }}"
                                class="e-ticket-qr__image"
                                width="280"
                                height="280"
                            />
                        </div>
                        <p class="e-ticket-qr__code" title="{{ $ticket->ticket_code }}">{{ $ticket->ticket_code }}</p>
                    </div>
                </div>

                <div class="e-ticket-divider"></div>

                <div>
                    <p class="e-ticket-eyebrow">{{ __('Participant') }}</p>
                    <p class="e-ticket-title">
                        {{ $rider->name }}
                        @if ($rider->nickname)
                            <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ $rider->nickname }})</span>
                        @endif
                    </p>

                    <div class="e-ticket-chips">
                        <div class="e-ticket-chip">
                            <span class="e-ticket-chip__label">{{ __('Bracket') }}</span>
                            <span class="e-ticket-chip__value">{{ $reg->bracket->name }}</span>
                        </div>
                        @if ($reg->package)
                            <div class="e-ticket-chip">
                                <span class="e-ticket-chip__label">{{ __('Package') }}</span>
                                <span class="e-ticket-chip__value">{{ $reg->package->name }}</span>
                            </div>
                        @endif
                        @if ($reg->number_plate ?? $rider->number_plate)
                            <div class="e-ticket-chip">
                                <span class="e-ticket-chip__label">{{ __('Number plate') }}</span>
                                <span class="e-ticket-chip__value">{{ $reg->number_plate ?: $rider->number_plate }}</span>
                            </div>
                        @endif
                        @if ($reg->jersey_size)
                            <div class="e-ticket-chip">
                                <span class="e-ticket-chip__label">{{ __('Jersey size') }}</span>
                                <span class="e-ticket-chip__value">{{ $reg->jersey_size }}</span>
                            </div>
                        @endif
                        <div class="e-ticket-chip e-ticket-chip--status">
                            <span class="e-ticket-chip__label">{{ __('Check-in') }}</span>
                            <div class="mt-0.5">
                                @include('tickets.partials.checkin-status', ['registration' => $reg])
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="e-ticket-footer">
                {{ __('Show this ticket at the event. You can save or share the link.') }}
            </p>
        </div>
    </div>
@endsection

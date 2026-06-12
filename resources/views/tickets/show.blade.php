@extends('layouts.bento-public')

@section('title')
    {{ __('E-Ticket') }} — {{ $event->title }}
@endsection

@section('content')
    <div class="mx-auto w-full max-w-3xl">
        <div class="bento-card overflow-hidden">
            <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-5 dark:border-zinc-800 sm:px-8 sm:py-6">
                <div class="min-w-0">
                    <p class="text-xs font-medium uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                        {{ __('Digital ticket') }}
                    </p>
                    <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                        {{ __('E-Ticket') }}
                    </h1>
                </div>
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-800/50">
                    <flux:icon name="check-circle" class="size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    {{ __('Valid') }}
                </span>
            </div>

            <div class="e-ticket-bento">
                <div class="e-ticket-tile e-ticket-tile--event">
                    <p class="e-ticket-tile__eyebrow">{{ __('Event') }}</p>
                    <p class="e-ticket-tile__title">{{ $event->title }}</p>

                    <dl class="mt-4 space-y-3">
                        @if ($event->location)
                            <div class="flex items-start gap-3">
                                <dt class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-900/80 dark:text-zinc-400" aria-hidden="true">
                                    <flux:icon name="map-pin" class="size-4" />
                                </dt>
                                <dd class="min-w-0 pt-0.5">
                                    <span class="e-ticket-detail-item__label">{{ __('Location') }}</span>
                                    <p class="mt-0.5 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $event->location->name }}</p>
                                </dd>
                            </div>
                        @endif
                        @if ($event->start_at)
                            <div class="flex items-start gap-3">
                                <dt class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-900/80 dark:text-zinc-400" aria-hidden="true">
                                    <flux:icon name="calendar-days" class="size-4" />
                                </dt>
                                <dd class="min-w-0 pt-0.5">
                                    <span class="e-ticket-detail-item__label">{{ __('Date') }}</span>
                                    <p class="mt-0.5 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $event->start_at->translatedFormat('l, j F Y') }}
                                    </p>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="e-ticket-tile e-ticket-tile--qr">
                    <p class="e-ticket-tile__eyebrow text-center">{{ __('Scan at entrance') }}</p>
                    <img
                        src="{{ route('tickets.qr', $ticket) }}"
                        alt="{{ __('QR Code') }}"
                        class="mt-4 h-44 w-44 rounded-2xl border border-zinc-200/80 bg-white p-2 shadow-sm dark:border-zinc-700/80 dark:bg-zinc-900 sm:h-52 sm:w-52"
                    />
                    <p class="mt-3 font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $ticket->ticket_code }}</p>
                </div>

                <div class="e-ticket-tile e-ticket-tile--participant">
                    <p class="e-ticket-tile__eyebrow">{{ __('Participant') }}</p>
                    <p class="e-ticket-tile__title">
                        {{ $rider->name }}
                        @if ($rider->nickname)
                            <span class="font-normal text-zinc-500 dark:text-zinc-400">({{ $rider->nickname }})</span>
                        @endif
                    </p>

                    <dl class="e-ticket-detail-grid">
                        <div class="e-ticket-detail-item">
                            <dt class="e-ticket-detail-item__label">{{ __('Bracket') }}</dt>
                            <dd class="e-ticket-detail-item__value">{{ $reg->bracket->name }}</dd>
                        </div>
                        @if ($reg->package)
                            <div class="e-ticket-detail-item">
                                <dt class="e-ticket-detail-item__label">{{ __('Package') }}</dt>
                                <dd class="e-ticket-detail-item__value">{{ $reg->package->name }}</dd>
                            </div>
                        @endif
                        @if ($reg->number_plate ?? $rider->number_plate)
                            <div class="e-ticket-detail-item">
                                <dt class="e-ticket-detail-item__label">{{ __('Number plate') }}</dt>
                                <dd class="e-ticket-detail-item__value">{{ $reg->number_plate ?: $rider->number_plate }}</dd>
                            </div>
                        @endif
                        @if ($reg->jersey_size)
                            <div class="e-ticket-detail-item">
                                <dt class="e-ticket-detail-item__label">{{ __('Jersey size') }}</dt>
                                <dd class="e-ticket-detail-item__value">{{ $reg->jersey_size }}</dd>
                            </div>
                        @endif
                        <div class="e-ticket-detail-item sm:col-span-2 lg:col-span-3">
                            <dt class="e-ticket-detail-item__label">{{ __('Check-in') }}</dt>
                            <dd class="mt-1">
                                @include('tickets.partials.checkin-status', ['registration' => $reg])
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <p class="border-t border-zinc-100 px-6 py-4 text-center text-xs leading-relaxed text-zinc-500 dark:border-zinc-800 dark:text-zinc-400 sm:px-8">
                {{ __('Show this ticket at the event. You can save or share the link.') }}
            </p>
        </div>
    </div>
@endsection

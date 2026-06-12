@extends('layouts.bento-public')

@section('title')
    {{ __('Ticket verification') }} — {{ $event->title }}
@endsection

@section('content')
    <div class="mx-auto w-full max-w-md">
        <div class="bento-card ticket-verify">
            <div class="ticket-verify__hero">
                <div class="ticket-verify__icon" aria-hidden="true">
                    <flux:icon name="check-circle" class="size-8 text-emerald-600 dark:text-emerald-400" />
                </div>
                <h1 class="ticket-verify__title">{{ __('Valid ticket') }}</h1>
                <p class="ticket-verify__event">{{ $event->title }}</p>
            </div>

            <dl class="ticket-verify__details">
                <div class="ticket-verify__row">
                    <dt>{{ __('Participant') }}</dt>
                    <dd>
                        {{ $rider->name }}
                        @if ($rider->nickname)
                            <span class="text-zinc-500 dark:text-zinc-400">({{ $rider->nickname }})</span>
                        @endif
                    </dd>
                </div>
                @if ($reg->number_plate ?? $rider->number_plate)
                    <div class="ticket-verify__row">
                        <dt>{{ __('Number plate') }}</dt>
                        <dd class="font-mono">{{ $reg->number_plate ?: $rider->number_plate }}</dd>
                    </div>
                @endif
                <div class="ticket-verify__row">
                    <dt>{{ __('Bracket') }}</dt>
                    <dd>{{ $reg->bracket->name }}</dd>
                </div>
                <div class="ticket-verify__row">
                    <dt>{{ __('Check-in') }}</dt>
                    <dd>
                        @include('tickets.partials.checkin-status', ['registration' => $reg])
                    </dd>
                </div>
            </dl>

            <p class="ticket-verify__code">{{ $ticket->ticket_code }}</p>
        </div>
    </div>
@endsection

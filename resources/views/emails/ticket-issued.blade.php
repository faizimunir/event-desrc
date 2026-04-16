<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Your e-ticket is ready') }}</title>
</head>
@php
    $registration = $ticket->registration()->with(['event.location', 'rider.user', 'bracket', 'package'])->first();
    $event = $registration?->event;
    $rider = $registration?->rider;
    $ticketUrl = route('tickets.show', $ticket->ticket_code);
    $qrUrl = route('tickets.qr', $ticket->ticket_code);
@endphp
<body style="font-family: sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <p>{{ __('Hello') }}, {{ $registration?->rider?->user?->name ?? $rider?->name ?? '' }}.</p>

    <p>{{ __('Your payment has been verified and your e-ticket is now ready.') }}</p>

    <h2 style="margin-top: 20px; margin-bottom: 8px;">{{ $event?->title }}</h2>
    @if($event?->location)
        <p style="margin: 0; font-size: 14px; color: #555;">{{ $event->location->name }}</p>
    @endif

    <div style="margin-top: 16px; font-size: 14px;">
        <p style="margin: 0;"><strong>{{ __('Rider') }}:</strong> {{ $rider?->name }}@if($rider?->nickname) ({{ $rider->nickname }})@endif</p>
        @if($registration?->bracket)
            <p style="margin: 0;"><strong>{{ __('Bracket') }}:</strong> {{ $registration->bracket->name }}</p>
        @endif
        @if($registration?->package)
            <p style="margin: 0;"><strong>{{ __('Package') }}:</strong> {{ $registration->package->name }}</p>
            <p style="margin: 0;"><strong>{{ __('Amount') }}:</strong> {{ $registration->package->formatted_payable_amount }}</p>
        @endif
    </div>

    <p style="margin-top: 24px;">{{ __('You can view your e-ticket using the button below:') }}</p>

    <p style="margin: 16px 0;">
        <a href="{{ $ticketUrl }}" style="display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 8px;">
            {{ __('Open e-ticket') }}
        </a>
    </p>

    <p style="font-size: 14px; color: #666;">{{ __('If the button does not work, copy and paste this link into your browser:') }}</p>
    <p style="font-size: 13px; word-break: break-all; color: #555;">{{ $ticketUrl }}</p>

    <h3 style="margin-top: 24px; margin-bottom: 8px; font-size: 16px;">{{ __('QR code for check-in') }}</h3>
    <p style="font-size: 13px; color: #555; margin-top: 0;">{{ __('Show this QR code at the venue during check-in.') }}</p>
    <p style="margin: 12px 0;">
        <img src="{{ $qrUrl }}" alt="{{ __('QR Code') }}" style="max-width: 220px; border-radius: 8px; border: 1px solid #e5e7eb;">
    </p>

    <p style="margin-top: 24px; font-size: 13px; color: #666;">{{ __('Thank you.') }}</p>
    <p style="margin: 0; font-size: 13px; color: #666;">— {{ config('app.name') }}</p>
</body>
</html>


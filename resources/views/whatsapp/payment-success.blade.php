{{ __('Hello') }}, {{ $recipientName }}.

{{ __('Your payment has been verified and your e-ticket is ready.') }}

📌 *{{ $eventTitle }}*

👤 *{{ __('Rider') }}:* {{ $registration->rider->name }}{{ $registration->rider->nickname ? ' (' . $registration->rider->nickname . ')' : '' }}
@if($registration->bracket)
📋 *{{ __('Bracket') }}:* {{ $registration->bracket->name }}
@endif
@if($registration->package)
📦 *{{ __('Package') }}:* {{ $registration->package->name }}
💰 *{{ __('Amount') }}:* {{ $registration->package->formatted_payable_amount }}
@endif

{{ __('You can access your e-ticket via the link below:') }}

{!! $ticketUrl !!}

{{ __('Please show the e-ticket or QR code at the venue during check-in.') }}

{{ __('Thank you.') }}
—
{{ config('app.name') }}


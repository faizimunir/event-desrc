{{ __('Hello') }}, {{ $recipientName }}.

{{ __('Your payment link for the event below is ready.') }}

📌 *{{ $eventTitle }}*

👤 *{{ __('Rider') }}:* {{ $registration->rider->name }}{{ $registration->rider->nickname ? ' (' . $registration->rider->nickname . ')' : '' }}
@if($registration->bracket)
📋 *{{ __('Bracket') }}:* {{ $registration->bracket->name }}
@endif
@if($registration->package)
📦 *{{ __('Package') }}:* {{ $registration->package->name }}
💰 *{{ __('Amount') }}:* {{ $registration->package->formatted_price }}
@endif

{{ __('Please complete your payment by opening the link below (valid for :minutes minutes):', ['minutes' => $paymentProofDeadlineMinutes]) }}

{!! $paymentLinkUrl !!}

{{ __('After opening the link, transfer to the account shown and upload your transfer proof.') }}

{{ __('Thank you.') }}
—
{{ config('app.name') }}

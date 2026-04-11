{{ __('Hello') }}, {{ $recipientName }}.

{{ __('Your payment link for the event below is ready.') }}

📌 *{{ $eventTitle }}*

👤 *{{ __('Rider') }}:* {{ $registration->rider->name }}{{ $registration->rider->nickname ? ' (' . $registration->rider->nickname . ')' : '' }}
@if($registration->bracket)
📋 *{{ __('Bracket') }}:* {{ $registration->bracket->name }}
@endif
@if($registration->package)
📦 *{{ __('Package') }}:* {{ $registration->package->name }}
💰 *{{ __('Amount') }}:* {{ $registration->package->formatted_payable_amount }}
@endif

{{ __('Please complete your payment by opening the link below (valid for :minutes minutes):', ['minutes' => $paymentProofDeadlineMinutes]) }}

{!! $paymentLinkUrl !!}

{{ __('After opening the link, use the exact transfer amount shown (package price plus a unique 3-digit code for manual bank transfer), then upload your proof if required.') }}

{{ __('Thank you.') }}
—
{{ config('app.name') }}

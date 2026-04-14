<p>{{ __('Hello :name,', ['name' => $name]) }}</p>
<p>{{ __('Your registration for :event was rejected.', ['event' => $eventTitle]) }}</p>
@if ($hadSuccessfulPayment)
<p>{{ __('If you already paid, please contact the organizer regarding a refund.') }}</p>
@endif
@if (! empty($reason))
<p>{{ __('Reason: :reason', ['reason' => $reason]) }}</p>
@endif

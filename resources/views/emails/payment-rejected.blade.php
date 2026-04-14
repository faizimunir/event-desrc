<p>{{ __('Hello :name,', ['name' => $name]) }}</p>
<p>{{ __('We could not verify your payment for :event.', ['event' => $eventTitle]) }}</p>
@if (! empty($reason))
<p>{{ __('Details: :reason', ['reason' => $reason]) }}</p>
@endif

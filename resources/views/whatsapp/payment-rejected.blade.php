{{ trim(__('Hello :name, we could not verify your payment for :event.', ['name' => $recipientName, 'event' => $eventTitle])) }}
@if ($reason)
{{ trim(__('Details: :reason', ['reason' => $reason])) }}
@endif

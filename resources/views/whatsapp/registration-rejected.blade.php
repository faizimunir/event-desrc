@if ($hadSuccessfulPayment)
{{ trim(__('Hello :name, your registration for :event was rejected. A refund will be arranged according to organizer policy.', ['name' => $recipientName, 'event' => $eventTitle])) }}
@else
{{ trim(__('Hello :name, your registration for :event was rejected.', ['name' => $recipientName, 'event' => $eventTitle])) }}
@endif
@if ($reason)
{{ trim(__('Reason: :reason', ['reason' => $reason])) }}
@endif

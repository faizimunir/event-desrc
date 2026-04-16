{{ trim(__('Moh maaf sebelumnya kak :name, aku tidak bisa verfikasi pembayaran untuk event :event.', ['name' => $recipientName, 'event' => $eventTitle])) }}
@if ($reason)
{{ trim(__('Alasan nya: :reason', ['reason' => $reason])) }}
@endif

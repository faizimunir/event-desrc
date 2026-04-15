@if ($hadSuccessfulPayment)
{{ trim(__('Mohon maaf kak :name, data registrasi untuk :event di tolak. Jika sudah membayar, aku akan mengembalikan uang nya.', ['name' => $recipientName, 'event' => $eventTitle])) }}
@else
{{ trim(__('Mohon maaf ya kak :name, data registrasi untuk :event di tolak.', ['name' => $recipientName, 'event' => $eventTitle])) }}
@endif
@if ($reason)
{{ trim(__('Alasan nya: :reason', ['reason' => $reason])) }}
@endif

@php
    $organizerUser = $registration->event?->organizer?->user;
@endphp

Terimakasih kak {{ $recipientName }} 👋
Registrasinya sudah beres ya 🙌  
E-ticket kamu juga sudah siap dipakai.

📌 *{{ $eventTitle }}*

👤 Rider: {{ $registration->rider->name }}{{ $registration->rider->nickname ? ' (' . $registration->rider->nickname . ')' : '' }}
@if($registration->bracket)
📋 Kelas: {{ $registration->bracket->name }}
@endif
@if($registration->package)
📦 Paket: {{ $registration->package->name }}
💰 Total: {{ $registration->package->formatted_payable_amount }}
@endif

Buka e-ticket lewat link ini ya:

{!! $ticketUrl !!}

Nanti di venue tinggal tunjukin e-ticket atau QR-nya pas check-in 🙏

Makasih ya kak 🙌

—
@if($organizerUser)
Kalau ada yang mau ditanyakan soal event, bisa hubungi panitia:
@if($organizerUser->name)
👤 {{ $organizerUser->name }}
@endif
@if($organizerUser->whatsapp)
📱 {{ $organizerUser->whatsapp }}
@endif
@else
Kalau butuh bantuan, hubungi panitia lewat kontak resmi event ya.
@endif

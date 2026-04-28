@php
    $organizerUser = $registration->event?->organizer?->user;
@endphp

Halo kak {{ $recipientName }} 👋
Aku dari {{ config('app.name') }} ya.

Link pembayaran untuk event *{{ $eventTitle }}* sudah siap 🙌

👤 Rider: {{ $registration->rider->name }}{{ $registration->rider->nickname ? ' (' . $registration->rider->nickname . ')' : '' }}
@if($registration->bracket)
📋 Kelas: {{ $registration->bracket->name }}
@endif
@if($registration->package)
📦 Paket: {{ $registration->package->name }}
💰 Total: {{ $registration->package->formatted_payable_amount }}
@endif

Bisa lanjut lewat link ini, masih aktif sekitar {{ $paymentProofDeadlineMinutes }} menit:

{{ $paymentLinkUrl }}

Nanti tinggal ikuti aja petunjuk di sana ya kak 🙏

Makasih ya 🙌

—
@if($organizerUser)
Kalau ada yang mau ditanyakan, bisa langsung hubungi panitia:
@if($organizerUser->name)
👤 {{ $organizerUser->name }}
@endif
@if($organizerUser->whatsapp)
📱 {{ $organizerUser->whatsapp }}
@endif
@else
Kalau butuh bantuan, bisa hubungi panitia lewat kontak resmi event ya.
@endif

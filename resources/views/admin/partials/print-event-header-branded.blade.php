{{-- Header cetak: logo event (kiri), nama event + info (tengah), logo DRC/DESRC (kanan) --}}
<div class="print-header">
    <div class="print-header-left">
        @if($event->logoUrl())
            <img src="{{ $event->logoUrl() }}" alt="{{ $event->title }}" class="logo logo-event">
        @endif
    </div>
    <div class="print-header-center">
        <div class="event-title">{{ $event->title }}</div>
        <div class="event-info">
            {{ $event->location?->name ?? '-' }} | {{ $event->start_at?->format('d M Y') ?? '-' }}
        </div>
    </div>
    <div class="print-header-right">
        <img src="{{ asset('DRCweb.png') }}" alt="DRC" class="logo logo-drc">
    </div>
</div>

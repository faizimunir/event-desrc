@props(['summary'])

@php
    $parts = array_filter([
        filled($summary['number_plate'] ?? null) ? '#'.$summary['number_plate'] : null,
        $summary['teams'] ?? null,
        $summary['bracket'] ?? null,
    ]);
@endphp

<div {{ $attributes->merge(['class' => 'checkin-success-callout']) }}>
    <flux:icon name="check-circle" class="checkin-success-callout__icon" aria-hidden="true" />
    <div class="min-w-0 flex-1">
        <p class="checkin-success-callout__name">{{ $summary['name'] }}</p>
        @if ($parts !== [])
            <p class="checkin-success-callout__meta">{{ implode(' · ', $parts) }}</p>
        @endif
    </div>
</div>

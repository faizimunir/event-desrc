@props([
    'event',
    'compact' => false,
])

@php($liveResultDayStatus = $event->liveResultDayStatus())

@if($liveResultDayStatus)
    <span @class([
        'inline-flex items-center rounded-full font-semibold uppercase',
        'gap-1.5 px-2.5 py-1 text-[11px] tracking-wider' => ! $compact,
        'gap-1 px-2 py-0.5 text-[10px] tracking-wide' => $compact,
        'bg-red-500/10 text-red-600 dark:bg-red-500/15 dark:text-red-400' => $liveResultDayStatus === 'live',
        'bg-zinc-500/10 text-zinc-600 dark:bg-zinc-500/15 dark:text-zinc-400' => $liveResultDayStatus === 'ended',
        'bg-blue-500/10 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400' => $liveResultDayStatus === 'upcoming',
    ])>
        @if($liveResultDayStatus === 'live')
            <span class="relative flex size-1.5">
                <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
            </span>
        @endif
        {{ $event->liveResultDayStatusLabel($liveResultDayStatus) }}
    </span>
@endif

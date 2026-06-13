@props([
    'event',
    'solid' => false,
    'source' => 'card',
    'variant' => 'default',
])

@php
    if ($source === 'effective') {
        $status = $event->effective_status;
        $label = $event->effectiveStatusLabel($status);
    } else {
        $status = $event->eventCardStatus();
        $label = $event->eventCardStatusLabel($status);
    }
@endphp

@if ($variant === 'edge')
    <div @class([
        'absolute inset-y-0 left-0 flex w-7 items-center justify-center border-r',
        'border-blue-500/25 bg-blue-500/5 text-blue-600 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400' => $status === 'published',
        'border-green-500/25 bg-green-500/5 text-green-600 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-400' => $status === 'open_regist',
        'border-orange-500/25 bg-orange-500/5 text-orange-600 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-400' => $status === 'closed_regist',
        'border-red-500/25 bg-red-500/5 text-red-600 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400' => $status === 'live',
        'border-zinc-400/25 bg-zinc-500/5 text-zinc-600 dark:border-zinc-500/30 dark:bg-zinc-500/10 dark:text-zinc-400' => in_array($status, ['done', 'draft'], true),
    ])>
        <span class="text-[10px] font-semibold uppercase italic tracking-widest [writing-mode:vertical-rl] rotate-180">
            {{ $label }}
        </span>
    </div>
@else
<span @class([
    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider',
    'bg-blue-600 text-white shadow-sm' => $solid && $status === 'published',
    'bg-green-600 text-white shadow-sm' => $solid && $status === 'open_regist',
    'bg-orange-500 text-white shadow-sm' => $solid && $status === 'closed_regist',
    'bg-red-600 text-white shadow-sm' => $solid && $status === 'live',
    'bg-zinc-600 text-white shadow-sm' => $solid && in_array($status, ['done', 'draft'], true),
    'bg-blue-500/10 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400' => ! $solid && $status === 'published',
    'bg-green-500/10 text-green-600 dark:bg-green-500/15 dark:text-green-400' => ! $solid && $status === 'open_regist',
    'bg-orange-500/10 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400' => ! $solid && $status === 'closed_regist',
    'bg-red-500/10 text-red-600 dark:bg-red-500/15 dark:text-red-400' => ! $solid && $status === 'live',
    'bg-zinc-500/10 text-zinc-600 dark:bg-zinc-500/15 dark:text-zinc-400' => ! $solid && in_array($status, ['done', 'draft'], true),
])>
    @if($status === 'live')
        <span class="relative flex size-1.5">
            <span @class([
                'absolute inline-flex size-full animate-ping rounded-full opacity-75',
                'bg-white' => $solid,
                'bg-red-500' => ! $solid,
            ])></span>
            <span @class([
                'relative inline-flex size-1.5 rounded-full',
                'bg-white' => $solid,
                'bg-red-500' => ! $solid,
            ])></span>
        </span>
    @endif
    {{ $label }}
</span>
@endif

<a
    href="{{ route('live-result.show', $event->slug) }}"
    wire:navigate
    class="live-result-list-item group"
>
    @if($event->logoUrl())
        <div class="live-result-list-item__logo">
            <img src="{{ $event->logoUrl() }}" alt="{{ $event->title }}" class="max-h-full max-w-full object-contain" />
        </div>
    @else
        <div class="live-result-list-item__icon">
            <flux:icon name="radio" variant="outline" class="size-6" />
        </div>
    @endif

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="font-semibold text-zinc-900 transition group-hover:text-orange-600 dark:text-white dark:group-hover:text-orange-400">
                {{ $event->title }}
            </h3>
            @php($liveResultDayStatus = $event->liveResultDayStatus())
            @if($liveResultDayStatus)
                <span @class([
                    'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide',
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
                    {{ $event->liveResultDayStatusLabel() }}
                </span>
            @endif
        </div>

        @if($event->start_at || $event->location)
            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                @if($event->start_at)
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="calendar-days" class="size-3.5 shrink-0" />
                        {{ $event->start_at->format('d M Y') }}
                    </span>
                @endif
                @if($event->location)
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="map-pin" class="size-3.5 shrink-0" />
                        {{ $event->location->name }}
                    </span>
                @endif
            </p>
        @endif
    </div>

    <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:group-hover:text-orange-400" />
</a>

<a
    href="{{ route('live-result.show', $event->slug) }}"
    wire:navigate
    class="live-result-list-item group"
>
    <div class="live-result-list-item__icon">
        <flux:icon name="radio" variant="outline" class="size-5" />
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="font-semibold text-zinc-900 transition group-hover:text-orange-600 dark:text-white dark:group-hover:text-orange-400">
                {{ $event->title }}
            </h3>
            <span class="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-600 dark:bg-red-500/15 dark:text-red-400">
                <span class="relative flex size-1.5">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                    <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                </span>
                Live
            </span>
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

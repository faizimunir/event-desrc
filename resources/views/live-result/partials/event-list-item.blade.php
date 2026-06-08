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
            @include('live-result.partials.day-status-badge', ['event' => $event, 'compact' => true])
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

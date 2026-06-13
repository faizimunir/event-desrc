<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by title, description, location…')"
            class="min-w-0 flex-1"
        />
    </div>

    @if ($this->events->isNotEmpty())
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->events as $event)
                <a
                    href="{{ route('events.show', $event) }}"
                    wire:navigate
                    wire:key="event-{{ $event->id }}"
                    class="group block rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2"
                >
                    <article class="h-full overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 dark:border-zinc-700 dark:bg-zinc-800/60 group-hover:-translate-y-0.5 group-hover:border-zinc-300 group-hover:shadow-lg dark:group-hover:border-zinc-600">
                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-zinc-100 dark:bg-zinc-700">
                            @if ($event->posterUrl())
                                <img
                                    src="{{ $event->posterUrl() }}"
                                    alt="{{ $event->title }}"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                />
                            @else
                                <div class="flex h-full items-center justify-center text-zinc-400">
                                    <flux:icon name="calendar" class="size-12" />
                                </div>
                            @endif
                            <div class="absolute left-3 top-3">
                                @include('partials.event-status-badge', ['event' => $event, 'source' => 'effective', 'solid' => true])
                            </div>
                        </div>
                        <div class="p-4 sm:p-5">
                            <h3 class="font-semibold text-zinc-900 line-clamp-2 transition group-hover:text-orange-600 dark:text-white dark:group-hover:text-orange-400">
                                {{ $event->title }}
                            </h3>
                            <p class="mt-2 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="calendar-days" class="size-4 shrink-0" />
                                <span>{{ $event->start_at->format('d M Y, H:i') }}</span>
                            </p>
                            @if ($event->end_at)
                                <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    <flux:icon name="clock" class="size-4 shrink-0" />
                                    <span>{{ $event->end_at->format('d M Y, H:i') }}</span>
                                </p>
                            @endif
                            @if ($event->location)
                                <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    <flux:icon name="map-pin" class="size-4 shrink-0" />
                                    <span class="line-clamp-1">{{ $event->location->name }}</span>
                                </p>
                            @endif
                            <span class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-orange-600 dark:text-orange-400">
                                {{ __('View details') }}
                                <flux:icon name="arrow-right" variant="mini" class="size-4 transition group-hover:translate-x-1" />
                            </span>
                        </div>
                    </article>
                </a>
            @endforeach
        </div>
    @else
        <div class="mt-4 rounded-xl border border-zinc-200 px-4 py-12 text-center dark:border-zinc-700">
            <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="calendar" class="size-6 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No events found.') }}</p>
        </div>
    @endif

    @if ($this->events->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->events->links() }}
        </div>
    @endif
</div>

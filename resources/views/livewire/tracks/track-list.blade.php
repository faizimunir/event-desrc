<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('track.create')
            <flux:button
                variant="primary"
                :href="route('events.tracks.create', $event)"
                wire:navigate
                icon="plus"
                square
                class="shrink-0"
                :aria-label="__('Add Track')"
            />
        @endcanAs

        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Tracks') }}
        </h2>
        <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
            {{ number_format($this->tracks->total()) }}
        </span>
    </div>

    @if ($this->tracks->isEmpty())
        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="traffic-cone" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No tracks found.') }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new track.') }}</p>
        </div>
    @else
        <div class="users-list-panel" wire:key="tracks-paged-p{{ $this->tracks->currentPage() }}">
            @foreach ($this->tracks as $track)
                @php
                    $canUpdate = auth()->user()->canAs('track.update') && auth()->user()->can('update', $track);
                    $metaParts = array_values(array_filter([
                        $track->material,
                        $track->long_track,
                    ]));
                @endphp

                <div wire:key="track-{{ $track->id }}" class="users-list-row group">
                    @if ($canUpdate)
                        <a
                            href="{{ route('events.tracks.edit', [$event, $track]) }}"
                            wire:navigate
                            class="flex min-w-0 flex-1 items-center gap-2.5"
                        >
                    @else
                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    @endif
                        @if ($track->photoTrackUrl())
                            <img
                                src="{{ $track->photoTrackUrl() }}"
                                alt=""
                                class="size-9 shrink-0 rounded-xl object-cover"
                            />
                        @else
                            <div class="users-list-avatar">
                                <flux:icon name="traffic-cone" variant="outline" class="size-4" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                {{ $track->name }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $metaParts !== [] ? implode(' · ', $metaParts) : '—' }}
                            </p>
                        </div>

                        @if ($canUpdate)
                            <flux:icon
                                name="chevron-right"
                                variant="mini"
                                class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                            />
                        @endif
                    @if ($canUpdate)
                        </a>
                    @else
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($this->tracks->hasPages())
        <div class="mt-4 pb-2">
            {{ $this->tracks->links() }}
        </div>
    @endif
</div>

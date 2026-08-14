<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('event.update')
            @can('update', $event)
                <flux:button
                    variant="primary"
                    :href="route('events.code-access.create', $event)"
                    wire:navigate
                    icon="plus"
                    square
                    class="shrink-0"
                    :aria-label="__('Add code')"
                />
            @endcan
        @endcanAs

        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by code or name…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Early Access') }}
        </h2>
        <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
            {{ number_format($this->codes->total()) }}
        </span>
    </div>

    @if ($this->codes->isEmpty())
        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="key" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No access codes yet.') }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new code.') }}</p>
        </div>
    @else
        <div class="users-list-panel" wire:key="codes-paged-p{{ $this->codes->currentPage() }}">
            @foreach ($this->codes as $ca)
                @php
                    $canUpdate = auth()->user()->canAs('event.update') && auth()->user()->can('update', $event);
                    $usedLabel = $ca->usage_limit
                        ? $ca->times_used.' / '.$ca->usage_limit
                        : (string) $ca->times_used;
                    $validLabel = ($ca->valid_from || $ca->valid_until)
                        ? (($ca->valid_from?->format('d/m/Y H:i') ?? '—').' → '.($ca->valid_until?->format('d/m/Y H:i') ?? '—'))
                        : __('Always');
                    $metaParts = array_values(array_filter([
                        $ca->name,
                        __('Used').': '.$usedLabel,
                        $validLabel,
                    ]));
                @endphp

                <div wire:key="code-access-{{ $ca->id }}" class="users-list-row group">
                    @if ($canUpdate)
                        <a
                            href="{{ route('events.code-access.edit', [$event, $ca]) }}"
                            wire:navigate
                            class="flex min-w-0 flex-1 items-center gap-2.5"
                        >
                    @else
                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    @endif
                        <div class="users-list-avatar">
                            <flux:icon name="key" variant="outline" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-mono text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                    {{ $ca->code }}
                                </p>
                                @if (! $ca->isValid())
                                    <span class="hidden shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 sm:inline dark:bg-zinc-700 dark:text-zinc-400">
                                        {{ __('Inactive') }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ implode(' · ', $metaParts) }}
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

    @if ($this->codes->hasPages())
        <div class="mt-4 pb-2">
            {{ $this->codes->links() }}
        </div>
    @endif
</div>

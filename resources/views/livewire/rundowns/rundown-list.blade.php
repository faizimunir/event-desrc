<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('rundown.create')
            <flux:button
                variant="primary"
                :href="route('events.rundowns.create', $event)"
                wire:navigate
                icon="plus"
                square
                class="shrink-0"
                :aria-label="__('Add Rundown')"
            />
        @endcanAs

        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by title or bracket…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="mb-3 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            {{ __('Rundown') }}
        </h2>
        <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
            {{ number_format($this->rundowns->total()) }}
        </span>
    </div>

    @if ($this->rundowns->isEmpty())
        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="clock" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No rundown entries yet.') }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Add race schedule slots. One slot can include multiple brackets.') }}</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700" wire:key="rundowns-paged-p{{ $this->rundowns->currentPage() }}">
            <table class="w-full border-collapse text-left text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="w-[38%] px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Time') }}
                        </th>
                        <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Activity / Brackets') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->rundowns as $rundown)
                        @php
                            $canUpdate = auth()->user()->canAs('rundown.update') && auth()->user()->can('update', $rundown);
                            $label = $rundown->displayLabel();
                            $bracketCount = $rundown->brackets->count();
                        @endphp
                        <tr wire:key="rundown-{{ $rundown->id }}" class="border-b border-zinc-200 last:border-b-0 dark:border-zinc-700">
                            @if ($canUpdate)
                                <td class="p-0" colspan="2">
                                    <a
                                        href="{{ route('events.rundowns.edit', [$event, $rundown]) }}"
                                        wire:navigate
                                        class="grid grid-cols-[38%_1fr] items-center hover:bg-orange-50/60 dark:hover:bg-orange-500/5"
                                    >
                                        <span class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                                            {{ $rundown->formattedTimeRange() }}
                                        </span>
                                        <span class="flex flex-wrap items-center gap-2 px-4 py-3">
                                            <span class="font-medium uppercase tracking-wide text-zinc-900 dark:text-zinc-100">
                                                {{ $label }}
                                            </span>
                                            @if ($bracketCount > 1)
                                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                                    {{ $bracketCount }} {{ __('brackets') }}
                                                </span>
                                            @endif
                                        </span>
                                    </a>
                                </td>
                            @else
                                <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                                    {{ $rundown->formattedTimeRange() }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium uppercase tracking-wide text-zinc-900 dark:text-zinc-100">
                                            {{ $label }}
                                        </span>
                                        @if ($bracketCount > 1)
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                                {{ $bracketCount }} {{ __('brackets') }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($this->rundowns->hasPages())
        <div class="mt-4 pb-2">
            {{ $this->rundowns->links() }}
        </div>
    @endif
</div>

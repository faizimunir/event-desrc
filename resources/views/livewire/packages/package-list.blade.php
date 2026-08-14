<div>
    <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
        @canAs('package.create')
            <flux:button
                variant="primary"
                :href="route('events.packages.create', $event)"
                wire:navigate
                icon="plus"
                square
                class="shrink-0"
                :aria-label="__('Add Package')"
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
            {{ __('Packages') }}
        </h2>
        <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
            {{ number_format($this->packages->total()) }}
        </span>
    </div>

    @if ($this->packages->isEmpty())
        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="cube" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No packages found.') }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new package.') }}</p>
        </div>
    @else
        <div class="users-list-panel" wire:key="packages-paged-p{{ $this->packages->currentPage() }}">
            @foreach ($this->packages as $package)
                @php
                    $canUpdate = auth()->user()->canAs('package.update') && auth()->user()->can('update', $package);
                    $remaining = $package->quota !== null ? $package->remainingQuota() : null;
                    $quotaLabel = $package->quota !== null
                        ? (($remaining !== null ? $remaining.' / ' : '').$package->quota)
                        : null;
                    $metaParts = [$package->formatted_payable_amount];
                    if ($quotaLabel) {
                        $metaParts[] = __('Quota').': '.$quotaLabel;
                    }
                    if ($package->hasAdminFee()) {
                        $metaParts[] = __('Admin').' '.$package->formatted_admin_fee;
                    }
                @endphp

                <div wire:key="package-{{ $package->id }}" class="users-list-row group">
                    @if ($canUpdate)
                        <a
                            href="{{ route('events.packages.edit', [$event, $package]) }}"
                            wire:navigate
                            class="flex min-w-0 flex-1 items-center gap-2.5"
                        >
                    @else
                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    @endif
                        <div class="users-list-avatar">
                            <flux:icon name="cube" variant="outline" class="size-4" />
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                    {{ $package->name }}
                                </p>
                                @if (! $package->isActive())
                                    <span class="hidden shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 sm:inline dark:bg-zinc-700 dark:text-zinc-400">
                                        {{ __('Not active') }}
                                    </span>
                                @endif
                                @if ($package->hide_quota)
                                    <span class="hidden shrink-0 rounded-full bg-red-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-red-600 sm:inline dark:bg-red-500/15 dark:text-red-400">
                                        {{ __('Quota Hidden') }}
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

    @if ($this->packages->hasPages())
        <div class="mt-4 pb-2">
            {{ $this->packages->links() }}
        </div>
    @endif
</div>

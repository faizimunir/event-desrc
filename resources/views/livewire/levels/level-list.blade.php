<div>
    <x-admin-hero-header
        :heading="__('Levels Management')"
        create-permission="level.create"
        :create-href="route('levels.create')"
        :create-label="__('Add Level')"
    >
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by code or name…')"
            class="min-w-0 flex-1"
        />
    </x-admin-hero-header>

    <div class="users-hero-content pb-6">
        <div class="flex items-center justify-between gap-3 py-3">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('All levels') }}</h2>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->levels->total()) }}
            </span>
        </div>

        @if ($this->levels->isNotEmpty())
            <div class="users-list-panel" wire:key="levels-paged-p{{ $this->levels->currentPage() }}">
                @foreach ($this->levels as $level)
                    <div wire:key="level-{{ $level->id }}" class="users-list-row group">
                        @canAs('level.update')
                            @can('update', $level)
                                <a href="{{ route('levels.edit', $level) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2.5">
                            @else
                                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                            @endcan
                        @else
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        @endcanAs
                            <div class="users-list-avatar">
                                {{ strtoupper(mb_substr($level->code ?: $level->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                        {{ $level->name }}
                                    </p>
                                    <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                        {{ $level->code }}
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Order') }}: {{ $level->order }}
                                </p>
                            </div>
                            @canAs('level.update')
                                @can('update', $level)
                                    <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400" />
                                @endcan
                            @endcanAs
                        @canAs('level.update')
                            @can('update', $level)
                                </a>
                            @else
                                </div>
                            @endcan
                        @else
                            </div>
                        @endcanAs
                    </div>
                @endforeach
            </div>
        @else
            <div class="users-list-panel px-4 py-12 text-center">
                <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="layers" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No levels found.') }}</p>
            </div>
        @endif

        @if ($this->levels->hasPages())
            <div class="mt-4 pb-2">{{ $this->levels->links() }}</div>
        @endif
    </div>
</div>

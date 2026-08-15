<div>
    <x-admin-hero-header
        :heading="__('RC Management')"
        create-permission="rc.create"
        :create-href="route('racing-committees.create')"
        :create-label="__('Add RC')"
    >
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />
    </x-admin-hero-header>

    <div class="users-hero-content pb-6">
        <div class="flex items-center justify-between gap-3 py-3">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('All racing committees') }}</h2>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->racingCommittees->total()) }}
            </span>
        </div>

        @if ($this->racingCommittees->isNotEmpty())
            <div class="users-list-panel" wire:key="rcs-paged-p{{ $this->racingCommittees->currentPage() }}">
                @foreach ($this->racingCommittees as $rc)
                    <div wire:key="rc-{{ $rc->id }}" class="users-list-row group">
                        @canAs('rc.update')
                            @can('update', $rc)
                                <a href="{{ route('racing-committees.edit', $rc) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2.5">
                            @else
                                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                            @endcan
                        @else
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        @endcanAs
                            <div class="users-list-avatar overflow-hidden p-0">
                                @if ($rc->photo_rc_url)
                                    <img src="{{ $rc->photo_rc_url }}" alt="{{ $rc->name }}" class="size-full object-cover" />
                                @else
                                    {{ $rc->initials() }}
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                    {{ $rc->name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($rc->link)
                                        {{ \Illuminate\Support\Str::limit($rc->link, 60) }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            @canAs('rc.update')
                                @can('update', $rc)
                                    <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400" />
                                @endcan
                            @endcanAs
                        @canAs('rc.update')
                            @can('update', $rc)
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
                    <flux:icon name="award" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No racing committees found.') }}</p>
            </div>
        @endif

        @if ($this->racingCommittees->hasPages())
            <div class="mt-4 pb-2">{{ $this->racingCommittees->links() }}</div>
        @endif
    </div>
</div>

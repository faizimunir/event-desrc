<div>
    <div class="users-hero-shell relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 shadow-[0_12px_32px_-14px_rgba(249,115,22,0.55)] dark:from-orange-600 dark:via-orange-600 dark:to-amber-600 lg:-mx-4">
        <div class="pointer-events-none absolute -right-8 -top-8 size-32 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>

        <div class="relative space-y-3 px-4 pb-3 pt-[max(0.5rem,env(safe-area-inset-top))] sm:px-5 sm:pb-4 lg:space-y-3.5 lg:pt-4">
            <div class="flex items-center gap-2.5 lg:hidden">
                <flux:sidebar.toggle
                    icon="bars-2"
                    inset="left"
                    class="!size-9 !rounded-xl !border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                />

                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs text-orange-100/80">
                        {{ __('Admin') }}
                    </p>
                    <h1 class="truncate text-sm font-semibold text-white">
                        {{ __('Riders Management') }}
                    </h1>
                </div>

                <flux:dropdown position="bottom" align="end">
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-white/25 bg-white/15 text-xs font-semibold text-white transition hover:bg-white/25"
                        aria-label="{{ __('Account menu') }}"
                    >
                        {{ auth()->user()->initials() }}
                    </button>

                    @include('partials.mobile-user-menu')
                </flux:dropdown>
            </div>

            <div class="hidden items-center justify-between gap-3 lg:flex">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-orange-100/90">
                        {{ __('Admin') }}
                    </p>
                    <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                        {{ __('Riders Management') }}
                    </h1>
                </div>

                @canAs('rider.create')
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="route('riders.create')"
                        wire:navigate
                        icon="plus"
                        class="shrink-0 !border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                    >
                        {{ __('Add Rider') }}
                    </flux:button>
                @endcanAs
            </div>

            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.500ms="search"
                    type="search"
                    :placeholder="__('Search…')"
                    class="min-w-0 flex-1"
                />

                @canAs('rider.create')
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="route('riders.create')"
                        wire:navigate
                        icon="plus"
                        square
                        class="users-hero-action shrink-0 !border-0 !bg-white !text-orange-600 hover:!bg-orange-50 lg:hidden"
                        :aria-label="__('Add Rider')"
                    />
                @endcanAs
            </div>
        </div>
    </div>

    <div class="users-hero-content">
        <div class="flex items-center justify-between gap-3 py-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('All riders') }}
                </h2>
            </div>

            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->riders->total()) }}
            </span>
        </div>

        @if ($this->riders->isNotEmpty())
            <div class="users-list-panel" wire:key="riders-paged-p{{ $this->riders->currentPage() }}">
                @foreach ($this->riders as $rider)
                    <div wire:key="rider-{{ $rider->id }}" class="users-list-row group">
                        @canAs('rider.update')
                            @can('update', $rider)
                                <a
                                    href="{{ route('riders.edit', $rider) }}"
                                    wire:navigate
                                    class="flex min-w-0 flex-1 items-center gap-2.5"
                                >
                            @else
                                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                            @endcan
                        @else
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        @endcanAs
                            <div class="users-list-avatar">
                                {{ strtoupper(mb_substr($rider->name, 0, 1)) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                        {{ $rider->name }}
                                    </p>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($rider->number_plate)
                                        <span class="inline-flex items-center gap-1">
                                            {{ $rider->nickname }} ·
                                            <flux:icon name="identification" class="size-3 shrink-0" />
                                            {{ $rider->number_plate }}
                                        </span>
                                    @elseif ($rider->dob || $rider->gender)
                                        <span class="inline-flex items-center gap-1">
                                            <flux:icon name="calendar-days" class="size-3 shrink-0" />
                                            {{ trim(($rider->birthYear() ?? '').' '.($rider->gender_label ?? '')) }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1.5">
                                @if ($rider->nickname)
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 sm:hidden dark:bg-zinc-700 dark:text-zinc-400">
                                        {{ $rider->nickname }}
                                    </span>
                                @endif

                                @if ($rider->number_plate && ($rider->dob || $rider->gender))
                                    <span class="hidden text-xs text-zinc-500 sm:inline dark:text-zinc-400">
                                        {{ trim(($rider->birthYear() ?? '').' '.($rider->gender_label ?? '')) }}
                                    </span>
                                @endif

                                @canAs('rider.update')
                                    @can('update', $rider)
                                        <flux:icon
                                            name="chevron-right"
                                            variant="mini"
                                            class="size-4 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                                        />
                                    @endcan
                                @endcanAs
                            </div>
                        @canAs('rider.update')
                            @can('update', $rider)
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
                    <flux:icon name="user" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No riders found.') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new rider.') }}</p>
            </div>
        @endif

        @if ($this->riders->hasPages())
            <div class="mt-4 pb-2">
                {{ $this->riders->links() }}
            </div>
        @endif
    </div>
</div>

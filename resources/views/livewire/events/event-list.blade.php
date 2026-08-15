<div>
    <div class="users-hero-shell sticky top-0 z-10 bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 shadow-[0_12px_32px_-14px_rgba(249,115,22,0.55)] dark:from-orange-600 dark:via-orange-600 dark:to-amber-600 lg:-mx-4">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-8 -top-8 size-32 rounded-full bg-white/10 blur-2xl"></div>
        </div>

        <div class="relative space-y-3 px-4 pb-3 pt-[max(0.5rem,env(safe-area-inset-top))] sm:px-5 sm:pb-4 lg:space-y-3.5 lg:pt-4">
            <div class="flex items-center gap-2.5 lg:hidden">
                <flux:sidebar.toggle
                    icon="bars-2"
                    inset="left"
                    class="!size-9 !rounded-xl !border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                />

                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                    <img
                        src="{{ asset('logo-mini-dark.webp') }}"
                        alt="{{ config('app.name') }}"
                        class="h-9 w-auto shrink-0 object-contain"
                    >
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs text-orange-100/80">
                            {{ auth()->user()->activeRoleLabel() }}
                        </p>
                        <h1 class="truncate text-sm font-semibold text-white">
                            {{ __('Events Management') }}
                        </h1>
                    </div>
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
                        {{ auth()->user()->activeRoleLabel() }}
                    </p>
                    <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                        {{ __('Events Management') }}
                    </h1>
                </div>

                @canAs('event.create')
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="route('events.create')"
                        wire:navigate
                        icon="plus"
                        class="shrink-0 !border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                    >
                        {{ __('Add Event') }}
                    </flux:button>
                @endcanAs
            </div>

            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.500ms="search"
                    type="search"
                    :placeholder="__('Search by title, description, location…')"
                    class="min-w-0 flex-1"
                />

                @canAs('event.create')
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="route('events.create')"
                        wire:navigate
                        icon="plus"
                        square
                        class="users-hero-action shrink-0 !border-0 !bg-white !text-orange-600 hover:!bg-orange-50 lg:hidden"
                        :aria-label="__('Add Event')"
                    />
                @endcanAs
            </div>
        </div>
    </div>

    <div class="users-hero-content">
        <div class="flex items-center justify-between gap-3 py-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('All events') }}
                </h2>
            </div>

            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->events->total()) }}
            </span>
        </div>

        @if ($this->events->isNotEmpty())
            <div class="flex flex-col gap-2">
                @foreach ($this->events as $event)
                    <a
                        href="{{ route('events.show', $event) }}"
                        wire:navigate
                        wire:key="event-{{ $event->id }}"
                        class="group relative flex items-center gap-4 overflow-hidden rounded-xl border border-zinc-200/80 bg-white py-3.5 pl-10 pr-4 transition duration-200 hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700/80 dark:bg-zinc-800/50 dark:hover:border-zinc-600 dark:hover:bg-zinc-800"
                    >
                        @include('partials.event-status-badge', ['event' => $event, 'source' => 'effective', 'variant' => 'edge'])

                        @if ($event->logoUrl())
                            <div class="flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-200/80 bg-zinc-50 p-1.5 dark:border-zinc-600 dark:bg-zinc-900/60">
                                <img
                                    src="{{ $event->logoUrl() }}"
                                    alt="{{ $event->title }}"
                                    class="max-h-full max-w-full object-contain"
                                />
                            </div>
                        @else
                            <div class="flex size-11 shrink-0 items-center justify-center rounded-lg bg-orange-500/10 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                                <flux:icon name="calendar-days" variant="outline" class="size-5" />
                            </div>
                        @endif

                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                {{ $event->title }}
                            </h3>

                            <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="inline-flex items-center gap-1.5">
                                    <flux:icon name="calendar-days" class="size-3.5 shrink-0" />
                                    {{ $event->start_at->format('d M Y, H:i') }}
                                </span>
                                @if ($event->end_at)
                                    <span class="inline-flex items-center gap-1.5">
                                        <flux:icon name="arrow-right" variant="mini" class="size-3 shrink-0 text-zinc-400" />
                                        {{ $event->end_at->format('d M Y, H:i') }}
                                    </span>
                                @endif
                            </p>
                            @if ($event->location)
                                <p class="mt-1 flex min-w-0 items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    <flux:icon name="map-pin" class="size-3.5 shrink-0" />
                                    <span class="truncate">{{ $event->location->name }}</span>
                                </p>
                            @endif
                        </div>

                        <div class="hidden shrink-0 text-right lg:block">
                            @if ($event->organizer)
                                <p class="text-xs font-medium uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                                    {{ __('Organizer') }}
                                </p>
                                <p class="mt-0.5 max-w-[10rem] truncate text-sm text-zinc-700 dark:text-zinc-300">
                                    {{ $event->organizer->name }}
                                </p>
                            @endif
                        </div>

                        <flux:icon
                            name="chevron-right"
                            variant="mini"
                            class="size-4 shrink-0 text-zinc-400 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:group-hover:text-orange-400"
                        />
                    </a>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-zinc-200 bg-zinc-50/80 px-4 py-12 text-center dark:border-zinc-700 dark:bg-zinc-800/30">
                <div class="mx-auto flex size-12 items-center justify-center rounded-xl bg-white shadow-sm dark:bg-zinc-800">
                    <flux:icon name="calendar" class="size-6 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No events found.') }}</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new event.') }}</p>
            </div>
        @endif

        @if ($this->events->hasPages())
            <div class="mt-4 flex justify-center">
                {{ $this->events->links() }}
            </div>
        @endif
    </div>
</div>

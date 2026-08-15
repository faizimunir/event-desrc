<x-layouts::app :title="__('Add registration') . ' — ' . $event->title" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
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
                                {{ $event->title }}
                            </p>
                            <h1 class="truncate text-sm font-semibold text-white">
                                {{ __('Add registration') }}
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
                            {{ $event->title }}
                        </p>
                        <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                            {{ __('Add registration') }}
                        </h1>
                    </div>

                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('events.show', [$event, 'tab' => 'registrations'])"
                        wire:navigate
                        icon="arrow-left"
                        class="shrink-0 !border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                    >
                        {{ __('Back') }}
                    </flux:button>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('events.show', [$event, 'tab' => 'registrations'])"
                        wire:navigate
                        icon="arrow-left"
                        class="users-hero-action shrink-0"
                        :aria-label="__('Back')"
                    />
                </div>
            </div>
        </div>

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <div class="max-w-xl rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden">
                <form action="{{ route('events.registrations.store', $event) }}" method="post" class="p-6 space-y-4">
                    @csrf

                    <div>
                        <flux:label for="rider_id" class="mb-1">{{ __('Rider') }}</flux:label>
                        <flux:select id="rider_id" name="rider_id" :placeholder="__('Select rider')" required>
                            <option value="">{{ __('— Select rider —') }}</option>
                            @foreach ($riders as $r)
                                <option value="{{ $r->id }}" @selected(old('rider_id') == $r->id)>
                                    {{ $r->name }}@if ($r->nickname) ({{ $r->nickname }})@endif
                                    @if ($r->user?->whatsapp) — {{ $r->user->whatsapp }}@endif
                                </option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:label for="bracket_id" class="mb-1">{{ __('Bracket') }}</flux:label>
                        <flux:select id="bracket_id" name="bracket_id" :placeholder="__('Select bracket')" required>
                            <option value="">{{ __('— Select bracket —') }}</option>
                            @foreach ($event->brackets as $b)
                                <option value="{{ $b->id }}" @selected(old('bracket_id') == $b->id)>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:label for="package_id" class="mb-1">{{ __('Package') }}</flux:label>
                        <flux:select id="package_id" name="package_id" :placeholder="__('Select package')" required>
                            <option value="">{{ __('— Select package —') }}</option>
                            @foreach ($event->packages as $pkg)
                                <option value="{{ $pkg->id }}" @selected(old('package_id') == $pkg->id)>
                                    {{ $pkg->name }} — {{ $pkg->formatted_payable_amount }}
                                </option>
                            @endforeach
                        </flux:select>
                    </div>

                    @if ($event->packages->contains(fn ($pkg) => $pkg->hasJerseyReward()))
                        <div>
                            <flux:label for="jersey_size" class="mb-1">{{ __('Jersey size') }}</flux:label>
                            <flux:select id="jersey_size" name="jersey_size" :placeholder="__('Select size')">
                                <option value="">{{ __('— Select size —') }}</option>
                                @foreach ($event->jerseySizeOptions() as $size)
                                    <option value="{{ $size }}" @selected(old('jersey_size') === $size)>{{ $size }}</option>
                                @endforeach
                            </flux:select>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Required when the selected package includes a jersey.') }}
                            </p>
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-2 pt-2">
                        <flux:button variant="primary" type="submit" icon="plus">
                            {{ __('Add registration') }}
                        </flux:button>
                        <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'registrations'])" wire:navigate type="button">
                            {{ __('Cancel') }}
                        </flux:button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts::app>

<x-layouts::app :title="__('Add Track')" :unified-header="true">
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
                                {{ __('Add Track') }}
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
                            {{ __('Add Track') }}
                        </h1>
                    </div>

                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('events.show', [$event, 'tab' => 'tracks'])"
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
                        :href="route('events.show', [$event, 'tab' => 'tracks'])"
                        wire:navigate
                        icon="arrow-left"
                        class="users-hero-action shrink-0"
                        :aria-label="__('Back')"
                    />
                </div>
            </div>
        </div>

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="post" action="{{ route('events.tracks.store', $event) }}" enctype="multipart/form-data" class="max-w-lg space-y-6">
                @csrf

                <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" placeholder="{{ __('e.g. Main circuit, Kids track') }}" required autofocus />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="material" type="text" :label="__('Material')" :value="old('material')" placeholder="{{ __('e.g. Dirt, Asphalt') }}" />
                @error('material')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="long_track" type="text" :label="__('Track length')" :value="old('long_track')" placeholder="{{ __('e.g. 1.2 km, 500 m') }}" />
                @error('long_track')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div>
                    <flux:label class="mb-2 block">{{ __('Photo track') }}</flux:label>
                    <input type="file" name="photo_track" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 dark:file:bg-zinc-700 dark:file:text-zinc-300" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Optional. JPG, PNG or WebP.') }}</p>
                    @error('photo_track')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">{{ __('Create Track') }}</flux:button>
                    <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'tracks'])" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>

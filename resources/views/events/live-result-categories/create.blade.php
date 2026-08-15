<x-layouts::app :title="__('Tambah Kategori')" :unified-header="true">
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
                                {{ __('Tambah Kategori') }}
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
                            {{ __('Tambah Kategori') }}
                        </h1>
                    </div>

                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('events.show', [$event, 'tab' => 'live-result'])"
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
                        :href="route('events.show', [$event, 'tab' => 'live-result'])"
                        wire:navigate
                        icon="arrow-left"
                        class="users-hero-action shrink-0"
                        :aria-label="__('Back')"
                    />
                </div>
            </div>
        </div>

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Kelola kategori dan round live result dari Google Sheets untuk event ini.') }}
            </p>

            @include('admin.live-result-categories.partials.form', ['event' => $event])
        </div>
    </div>
</x-layouts::app>

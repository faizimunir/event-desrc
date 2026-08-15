@props([
    'heading',
    'subheading' => null,
    'createPermission' => null,
    'createHref' => null,
    'createLabel' => null,
])

@php
    $subheading = $subheading ?? auth()->user()?->activeRoleLabel() ?? __('Admin');
    $hasCreate = filled($createHref) && filled($createLabel);
    $gateCreate = filled($createPermission);
    $hasToolbar = ! $slot->isEmpty() || $hasCreate;
@endphp

<div {{ $attributes->class('users-hero-shell sticky top-0 z-10 bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 shadow-[0_12px_32px_-14px_rgba(249,115,22,0.55)] dark:from-orange-600 dark:via-orange-600 dark:to-amber-600 lg:-mx-4') }}>
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
                        {{ $subheading }}
                    </p>
                    <h1 class="truncate text-sm font-semibold text-white">
                        {{ $heading }}
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
                    {{ $subheading }}
                </p>
                <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                    {{ $heading }}
                </h1>
            </div>

            @if ($hasCreate)
                @if ($gateCreate)
                    @canAs($createPermission)
                        <flux:button
                            variant="primary"
                            size="sm"
                            :href="$createHref"
                            wire:navigate
                            icon="plus"
                            class="shrink-0 !border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                        >
                            {{ $createLabel }}
                        </flux:button>
                    @endcanAs
                @else
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="$createHref"
                        wire:navigate
                        icon="plus"
                        class="shrink-0 !border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                    >
                        {{ $createLabel }}
                    </flux:button>
                @endif
            @endif
        </div>

        @if ($hasToolbar)
            <div class="flex items-center gap-2">
                {{ $slot }}

                @if ($hasCreate)
                    @if ($gateCreate)
                        @canAs($createPermission)
                            <flux:button
                                variant="primary"
                                size="sm"
                                :href="$createHref"
                                wire:navigate
                                icon="plus"
                                square
                                class="users-hero-action shrink-0 !border-0 !bg-white !text-orange-600 hover:!bg-orange-50 lg:hidden"
                                :aria-label="$createLabel"
                            />
                        @endcanAs
                    @else
                        <flux:button
                            variant="primary"
                            size="sm"
                            :href="$createHref"
                            wire:navigate
                            icon="plus"
                            square
                            class="users-hero-action shrink-0 !border-0 !bg-white !text-orange-600 hover:!bg-orange-50 lg:hidden"
                            :aria-label="$createLabel"
                        />
                    @endif
                @endif
            </div>
        @endif
    </div>
</div>

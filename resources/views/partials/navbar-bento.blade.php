<div
    x-data="{ mobileMenuOpen: false }"
    x-on:keydown.escape.window="mobileMenuOpen = false"
    x-on:resize.window="if (window.innerWidth >= 1024) mobileMenuOpen = false"
    x-effect="document.body.classList.toggle('overflow-hidden', mobileMenuOpen)"
>
    <div class="bento-nav">
        <div class="bento-card px-3 py-2 sm:px-4 sm:py-2.5">
            <flux:header class="!min-h-12 !border-0 !bg-transparent !shadow-none !px-0">
                <flux:button
                    icon="bars-2"
                    variant="subtle"
                    square
                    class="!rounded-xl lg:hidden"
                    x-on:click="mobileMenuOpen = true"
                    aria-label="{{ __('Open menu') }}"
                    x-bind:aria-expanded="mobileMenuOpen.toString()"
                />

                <flux:brand href="{{ route('home') }}" logo="{{ asset('logo-light.webp') }}" class="dark:hidden" />
                <flux:brand href="{{ route('home') }}" logo="{{ asset('logo-dark.webp') }}" class="hidden dark:flex" />

                <flux:navbar class="-mb-px max-lg:hidden">
                    <flux:navbar.item icon="calendar" href="{{ route('events.public.index') }}" wire:navigate class="!rounded-xl">Events</flux:navbar.item>
                    <flux:navbar.item icon="radio" href="{{ route('live-result.index') }}" wire:navigate class="!rounded-xl !bg-red-500 !text-white hover:!bg-red-500 focus:!ring-red-600 dark:!bg-red-600 dark:hover:!bg-red-500">{{ __('Live Result') }}</flux:navbar.item>
                </flux:navbar>

                <flux:spacer />

                <flux:navbar class="me-2 sm:me-3">
                    <a href="{{ route('orders.index') }}" class="relative inline-flex" aria-label="{{ __('My orders') }}">
                        <flux:button icon="shopping-cart" variant="subtle" aria-label="{{ __('My orders') }}" class="!rounded-xl" />
                        @if (isset($pendingOrdersCount) && $pendingOrdersCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white ring-2 ring-white dark:ring-zinc-900">
                                {{ $pendingOrdersCount > 99 ? '99+' : $pendingOrdersCount }}
                            </span>
                        @endif
                    </a>
                    <flux:button x-data x-on:click="$flux.dark = ! $flux.dark" icon="moon" variant="subtle" aria-label="Toggle dark mode" class="!rounded-xl" />
                </flux:navbar>

                @auth
                    <flux:dropdown position="top" align="end">
                        <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                        <flux:menu>
                            <flux:menu.radio.group>
                                <div class="p-0 text-sm font-normal">
                                    <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                        <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                                        <div class="grid flex-1 text-start text-sm leading-tight">
                                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            </flux:menu.radio.group>

                            @include('partials.role-switcher')

                            <flux:menu.separator />

                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('dashboard')" icon="squares-2x2" wire:navigate>
                                    {{ __('Dashboard') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <flux:menu.radio.group>
                                <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                                    {{ __('Settings') }}
                                </flux:menu.item>
                            </flux:menu.radio.group>

                            <flux:menu.separator />

                            <form method="POST" action="{{ route('logout') }}" class="w-full">
                                @csrf
                                <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                    class="w-full cursor-pointer" data-test="logout-button">
                                    {{ __('Log Out') }}
                                </flux:menu.item>
                            </form>
                        </flux:menu>
                    </flux:dropdown>
                @else
                    <flux:navbar class="gap-2 max-lg:hidden">
                        <flux:button variant="ghost" href="{{ route('login') }}" wire:navigate class="!rounded-xl">Masuk</flux:button>
                        <flux:button variant="primary" href="{{ route('register') }}" wire:navigate class="!rounded-xl">Daftar</flux:button>
                    </flux:navbar>

                    <flux:dropdown position="top" align="end" class="lg:hidden">
                        <flux:profile aria-label="{{ __('Account') }}" />
                        <flux:menu>
                            <flux:menu.item href="{{ route('login') }}" wire:navigate>Masuk</flux:menu.item>
                            <flux:menu.separator />
                            <flux:menu.item href="{{ route('register') }}" wire:navigate>Daftar</flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                @endauth
            </flux:header>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div
        x-cloak
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 lg:hidden"
        aria-hidden="true"
    >
        <button
            type="button"
            class="absolute inset-0 bg-zinc-900/25 backdrop-blur-[2px]"
            x-on:click="mobileMenuOpen = false"
            aria-label="{{ __('Close menu') }}"
        ></button>

        <div
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-x-3"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 -translate-x-3"
            class="bento-mobile-menu absolute left-4 top-28 w-[min(18rem,calc(100vw-2rem))] sm:left-6"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('Menu') }}"
        >
            <div class="flex items-center justify-between gap-3 px-1 pb-3">
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Menu') }}</span>
                <flux:button
                    icon="x-mark"
                    variant="subtle"
                    square
                    class="!rounded-xl"
                    x-on:click="mobileMenuOpen = false"
                    aria-label="{{ __('Close menu') }}"
                />
            </div>

            <nav class="flex flex-col gap-1.5">
                <a
                    href="{{ route('events.public.index') }}"
                    wire:navigate
                    x-on:click="mobileMenuOpen = false"
                    @class([
                        'bento-mobile-menu__item',
                        'bento-mobile-menu__item--current' => request()->routeIs('events.public.index'),
                    ])
                >
                    <flux:icon name="calendar" variant="outline" class="size-4 shrink-0" />
                    <span>Events</span>
                </a>

                <a
                    href="{{ route('live-result.index') }}"
                    wire:navigate
                    x-on:click="mobileMenuOpen = false"
                    class="bento-mobile-menu__item bento-mobile-menu__item--live"
                >
                    <flux:icon name="radio" variant="outline" class="size-4 shrink-0" />
                    <span>{{ __('Live Result') }}</span>
                </a>

                <a
                    href="https://app.desrc.id/"
                    target="_blank"
                    rel="noopener noreferrer"
                    x-on:click="mobileMenuOpen = false"
                    class="bento-mobile-menu__item"
                >
                    <flux:icon name="squares-2x2" variant="outline" class="size-4 shrink-0" />
                    <span>{{ __('Aplikasi') }}</span>
                </a>
            </nav>
        </div>
    </div>
</div>

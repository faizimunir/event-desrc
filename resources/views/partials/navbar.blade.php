<flux:header container class="sticky top-0 z-50 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <flux:brand href="{{ route('home') }}" logo="{{ asset('logo-light.webp') }}" class="dark:hidden" />
    <flux:brand href="{{ route('home') }}" logo="{{ asset('logo-dark.webp') }}" class="hidden dark:flex" />

    <flux:navbar class="-mb-px max-lg:hidden">
        <flux:navbar.item icon="calendar" href="{{ route('events.public.index') }}" wire:navigate>Events</flux:navbar.item>
        <flux:navbar.item icon="radio" href="{{ route('live-result.index') }}" wire:navigate class="!bg-red-500 !text-white hover:!bg-red-500 focus:!ring-red-600 dark:!bg-red-600 dark:hover:!bg-red-500">{{ __('Live Result') }}</flux:navbar.item>
        <flux:navbar.item icon="squares-2x2" href="https://app.desrc.id/" target="_blank" rel="noopener noreferrer">{{ __('Aplikasi') }}</flux:navbar.item>
    </flux:navbar>

    <flux:spacer />

    <flux:navbar class="me-4">
        <a href="{{ route('orders.index') }}" class="relative inline-flex" aria-label="{{ __('My orders') }}">
            <flux:button icon="shopping-cart" variant="subtle" aria-label="{{ __('My orders') }}" />
            @if (isset($pendingOrdersCount) && $pendingOrdersCount > 0)
                <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white ring-2 ring-zinc-50 dark:ring-zinc-900">
                    {{ $pendingOrdersCount > 99 ? '99+' : $pendingOrdersCount }}
                </span>
            @endif
        </a>
        <flux:button x-data x-on:click="$flux.dark = ! $flux.dark" icon="moon" variant="subtle" aria-label="Toggle dark mode" />
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
            <flux:button variant="ghost" href="{{ route('login') }}" wire:navigate>Masuk</flux:button>
            <flux:button variant="primary" href="{{ route('register') }}" wire:navigate>Daftar</flux:button>
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

<flux:sidebar sticky collapsible="mobile" class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700 pt-18">

    <flux:sidebar.nav class="space-y-2">
        <flux:sidebar.item icon="calendar" href="{{ route('events.public.index') }}" wire:navigate :current="request()->routeIs('events.public.index')">Events</flux:sidebar.item>
        <flux:sidebar.item icon="radio" href="{{ route('live-result.index') }}" wire:navigate :current="request()->routeIs('live-result.*')" class="bg-red-600 text-white data-[current]:!bg-red-600 data-[current]:!text-white hover:!bg-red-500 focus:!bg-red-600 hover:!text-white">{{ __('Live Result') }}</flux:sidebar.item>
        <flux:sidebar.item icon="squares-2x2" href="https://app.desrc.id/" target="_blank" rel="noopener noreferrer">{{ __('Aplikasi') }}</flux:sidebar.item>
    </flux:sidebar.nav>
</flux:sidebar>

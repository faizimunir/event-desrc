<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    <flux:sidebar sticky collapsible
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <flux:sidebar.brand
                href="#"
                logo="https://fluxui.dev/img/demo/logo.png"
                logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
                name="Acme Inc."
            />
            <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <div class="px-3 py-2 in-data-flux-sidebar-collapsed-desktop:hidden" data-flux-sidebar-group>
                <div class="text-sm text-zinc-400 font-medium leading-none">{{ __('Platform') }}</div>
            </div>
            <div class="block space-y-[2px]">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                @canAs('user.read')
                <flux:sidebar.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')"
                    wire:navigate>
                    {{ __('Users') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('event.read')
                <flux:sidebar.item icon="calendar" :href="route('events.index')" :current="request()->routeIs('events.*')"
                    wire:navigate>
                    {{ __('Events') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="printer" :href="route('print-center.index')" :current="request()->routeIs('print-center.*')"
                    wire:navigate>
                    {{ __('Print Center') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('payment.read')
                <flux:sidebar.item icon="banknotes" :href="route('payments.index')" :current="request()->routeIs('payments.*')"
                    wire:navigate>
                    {{ __('Payments') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('rider.read')
                <flux:sidebar.item icon="user" :href="route('riders.index')" :current="request()->routeIs('riders.*')"
                    wire:navigate>
                    {{ __('Riders') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('account.read')
                <flux:sidebar.item icon="building-library" :href="route('accounts.index')" :current="request()->routeIs('accounts.*')"
                    wire:navigate>
                    {{ __('Accounts') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('location.read')
                <flux:sidebar.item icon="map-pin" :href="route('locations.index')" :current="request()->routeIs('locations.*')"
                    wire:navigate>
                    {{ __('Locations') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('reward.read')
                <flux:sidebar.item icon="gift" :href="route('rewards.index')" :current="request()->routeIs('rewards.*')"
                    wire:navigate>
                    {{ __('Rewards') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('level.read')
                <flux:sidebar.item icon="layers" :href="route('levels.index')" :current="request()->routeIs('levels.*')"
                    wire:navigate>
                    {{ __('Levels') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('organizer.read')
                <flux:sidebar.item icon="building-2" :href="route('organizers.index')" :current="request()->routeIs('organizers.*')"
                    wire:navigate>
                    {{ __('Organizers') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('team.read')
                <flux:sidebar.item icon="user-group" :href="route('teams.index')" :current="request()->routeIs('teams.*')"
                    wire:navigate>
                    {{ __('Teams') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('mc.read')
                <flux:sidebar.item icon="mic" :href="route('master-of-ceremonies.index')" :current="request()->routeIs('master-of-ceremonies.*')"
                    wire:navigate>
                    {{ __('Master of Ceremonies') }}
                </flux:sidebar.item>
                @endcanAs
                @canAs('rc.read')
                <flux:sidebar.item icon="award" :href="route('racing-committees.index')" :current="request()->routeIs('racing-committees.*')"
                    wire:navigate>
                    {{ __('Racing Committees') }}
                </flux:sidebar.item>
                @endcanAs
                @if (auth()->user()->hasRole('super_admin'))
                    <flux:sidebar.item icon="shield-check" :href="route('roles.index')"
                        :current="request()->routeIs('roles.*')" wire:navigate>
                        {{ __('Roles') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="key" :href="route('permissions.index')"
                        :current="request()->routeIs('permissions.*')" wire:navigate>
                        {{ __('Permissions') }}
                    </flux:sidebar.item>
                @endif
            </div>
        </flux:sidebar.nav>
<flux:sidebar.spacer />
        <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
    <flux:radio value="light" icon="sun" />
    <flux:radio value="dark" icon="moon" />
    <flux:radio value="system" icon="computer-desktop" />
</flux:radio.group>

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header
        class="lg:hidden sticky top-0 z-50 bg-white/80 dark:bg-zinc-800/80 backdrop-blur border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
        <flux:spacer />
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
    </flux:header>

    {{ $slot }}

    @persist('toast')
        <flux:toast />
    @endpersist

    <livewire:flash-toast />

    @fluxScripts
</body>

</html>

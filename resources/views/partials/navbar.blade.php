<flux:header container class="sticky top-0 z-50 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <flux:brand href="{{ route('home') }}" logo="{{ asset('logo-light.webp') }}" class="max-lg:hidden dark:hidden" />
    <flux:brand href="{{ route('home') }}" logo="{{ asset('logo-dark.webp') }}" class="max-lg:hidden! hidden dark:flex" />

    <flux:navbar class="-mb-px max-lg:hidden">
        <flux:navbar.item icon="calendar" href="{{ route('home') }}#events">Events</flux:navbar.item>

    </flux:navbar>

    <flux:spacer />

    <flux:navbar class="me-4">
        <flux:button x-data x-on:click="$flux.dark = ! $flux.dark" icon="moon" variant="subtle" aria-label="Toggle dark mode" />
    </flux:navbar>

    @auth
        <flux:dropdown position="top" align="start">
            <flux:profile :initials="auth()->user()->initials()" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="flex items-center gap-2 px-1 py-1.5">
                        <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                        <div class="grid flex-1 text-start text-sm leading-tight">
                            <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                            <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                        </div>
                    </div>
                </flux:menu.radio.group>
                <flux:menu.separator />
                <flux:menu.item icon="squares-2x2" href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:menu.item>
                <flux:menu.separator />
                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer">Logout</flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    @else
        <flux:navbar class="gap-2">
            <flux:button variant="ghost" href="{{ route('login') }}" wire:navigate>Masuk</flux:button>
            <flux:button variant="primary" href="{{ route('register') }}" wire:navigate>Daftar</flux:button>
        </flux:navbar>
    @endauth
</flux:header>

<flux:sidebar sticky collapsible="mobile" class="lg:hidden bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
    <flux:sidebar.header>
        <flux:sidebar.brand
            href="{{ route('home') }}"
            logo="https://fluxui.dev/img/demo/logo.png"
            logo:dark="https://fluxui.dev/img/demo/dark-mode-logo.png"
            name="{{ config('app.name') }}"
        />
        <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
    </flux:sidebar.header>

    <flux:sidebar.nav>
        <flux:sidebar.item icon="home" href="{{ route('home') }}" :current="request()->routeIs('home')">Home</flux:sidebar.item>
        <flux:sidebar.item icon="calendar" href="{{ route('home') }}#events">Events</flux:sidebar.item>
        @auth
            <flux:sidebar.item icon="squares-2x2" href="{{ route('dashboard') }}" wire:navigate>Dashboard</flux:sidebar.item>
            <flux:sidebar.item icon="calendar-days" href="{{ route('events.index') }}" wire:navigate>Kelola Event</flux:sidebar.item>
            <flux:sidebar.item icon="banknotes" href="{{ route('payments.index') }}" wire:navigate>Pembayaran</flux:sidebar.item>
        @endauth

        <flux:sidebar.group expandable heading="Favorites" class="grid">
            <flux:sidebar.item href="#">Marketing site</flux:sidebar.item>
            <flux:sidebar.item href="#">Android app</flux:sidebar.item>
            <flux:sidebar.item href="#">Brand guidelines</flux:sidebar.item>
        </flux:sidebar.group>
    </flux:sidebar.nav>

    <flux:sidebar.spacer />

    <flux:sidebar.nav>
        <flux:sidebar.item icon="cog-6-tooth" href="#">Settings</flux:sidebar.item>
        <flux:sidebar.item icon="information-circle" href="#">Help</flux:sidebar.item>
    </flux:sidebar.nav>
</flux:sidebar>

<x-layouts::app :title="$user->name" :unified-header="true">
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
                                {{ __('Users') }}
                            </p>
                            <h1 class="truncate text-sm font-semibold text-white">
                                {{ $user->name }}
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
                            {{ __('Users') }}
                        </p>
                        <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                            {{ $user->name }}
                        </h1>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <flux:button
                            variant="ghost"
                            size="sm"
                            :href="route('users.index')"
                            wire:navigate
                            icon="arrow-left"
                            class="!border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                        >
                            {{ __('Back') }}
                        </flux:button>

                        @canAs('user.update')
                            @can('update', $user)
                                <flux:button
                                    variant="primary"
                                    size="sm"
                                    :href="route('users.edit', $user)"
                                    wire:navigate
                                    icon="pencil"
                                    class="!border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                                >
                                    {{ __('Edit User') }}
                                </flux:button>
                            @endcan
                        @endcanAs
                    </div>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('users.index')"
                        wire:navigate
                        icon="arrow-left"
                        class="users-hero-action shrink-0"
                        :aria-label="__('Back')"
                    />

                    @canAs('user.update')
                        @can('update', $user)
                            <flux:button
                                variant="primary"
                                size="sm"
                                :href="route('users.edit', $user)"
                                wire:navigate
                                icon="pencil"
                                class="min-w-0 flex-1 !border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                            >
                                {{ __('Edit User') }}
                            </flux:button>
                        @endcan
                    @endcanAs
                </div>
            </div>
        </div>

        <div class="users-hero-content flex flex-1 flex-col gap-6 pt-4 pb-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="flex items-start gap-3">
                    <div class="users-list-avatar size-12 text-sm">
                        {{ $user->initials() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $user->name }}
                            </h2>
                            @foreach ($user->roles as $role)
                                <span class="shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                    {{ str_replace('_', ' ', $role->name) }}
                                </span>
                            @endforeach
                        </div>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                                <dd class="mt-1 truncate text-sm text-zinc-800 dark:text-zinc-200">{{ $user->email ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('WhatsApp') }}</dt>
                                <dd class="mt-1 truncate text-sm text-zinc-800 dark:text-zinc-200">{{ $user->whatsapp ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Riders') }}
                    </h2>
                    <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                        {{ number_format($user->riders->count()) }}
                    </span>
                </div>

                @if ($user->riders->isEmpty())
                    <div class="users-list-panel px-4 py-10 text-center">
                        <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="user" class="size-5 text-zinc-400" />
                        </div>
                        <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No riders linked to this account.') }}</p>
                    </div>
                @else
                    <div class="users-list-panel">
                        @foreach ($user->riders as $rider)
                            <div class="users-list-row group">
                                @canAs('rider.read')
                                    <a href="{{ route('riders.show', $rider) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2.5">
                                @else
                                    <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                @endcanAs
                                    <div class="users-list-avatar">
                                        {{ strtoupper(mb_substr($rider->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                            {{ $rider->name }}
                                        </p>
                                        <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            @if ($rider->nickname || $rider->number_plate)
                                                {{ collect([$rider->nickname, $rider->number_plate])->filter()->implode(' · ') }}
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                    @canAs('rider.read')
                                        <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400" />
                                    @endcanAs
                                @canAs('rider.read')
                                    </a>
                                @else
                                    </div>
                                @endcanAs
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ __('Events') }}
                    </h2>
                    <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                        {{ number_format($user->registrations->count()) }}
                    </span>
                </div>

                @if ($user->registrations->isEmpty())
                    <div class="users-list-panel px-4 py-10 text-center">
                        <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="calendar" class="size-5 text-zinc-400" />
                        </div>
                        <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No event registrations yet.') }}</p>
                    </div>
                @else
                    <div class="users-list-panel">
                        @foreach ($user->registrations as $registration)
                            @php
                                $badgeColor = match ($registration->status) {
                                    'approved' => 'green',
                                    'pending' => 'yellow',
                                    'rejected' => 'red',
                                    'cancelled' => 'zinc',
                                    default => 'zinc',
                                };
                            @endphp
                            <div class="users-list-row group">
                                @canAs('event.read')
                                    <a href="{{ route('events.registrations.show', [$registration->event, $registration]) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2.5">
                                @else
                                    <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                @endcanAs
                                    <div class="users-list-avatar">
                                        <flux:icon name="calendar" class="size-4" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                                {{ $registration->event?->title ?? __('Event') }}
                                            </p>
                                            <flux:badge :color="$badgeColor" size="sm" class="shrink-0">
                                                {{ $registration->status_label }}
                                            </flux:badge>
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ collect([
                                                $registration->rider?->name,
                                                $registration->bracket?->name,
                                                $registration->created_at?->format('d/m/Y'),
                                            ])->filter()->implode(' · ') }}
                                        </p>
                                    </div>
                                    @canAs('event.read')
                                        <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400" />
                                    @endcanAs
                                @canAs('event.read')
                                    </a>
                                @else
                                    </div>
                                @endcanAs
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-layouts::app>

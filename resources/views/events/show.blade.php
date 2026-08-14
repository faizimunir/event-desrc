<x-layouts::app :title="$event->title" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <div class="users-hero-shell relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 shadow-[0_12px_32px_-14px_rgba(249,115,22,0.55)] dark:from-orange-600 dark:via-orange-600 dark:to-amber-600 lg:-mx-4">
            <div class="pointer-events-none absolute -right-8 -top-8 size-32 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>

            <div class="relative space-y-3 px-4 pb-3 pt-[max(0.5rem,env(safe-area-inset-top))] sm:px-5 sm:pb-4 lg:space-y-3.5 lg:pt-4">
                <div class="flex items-center gap-2.5 lg:hidden">
                    <flux:sidebar.toggle
                        icon="bars-2"
                        inset="left"
                        class="!size-9 !rounded-xl !border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                    />

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs text-orange-100/80">
                            {{ __('Events') }}
                        </p>
                        <h1 class="truncate text-sm font-semibold text-white">
                            {{ $event->title }}
                        </h1>
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
                            {{ __('Events') }}
                        </p>
                        <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                            {{ $event->title }}
                        </h1>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        <flux:button
                            variant="ghost"
                            size="sm"
                            :href="route('events.index')"
                            wire:navigate
                            icon="arrow-left"
                            class="!border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                        >
                            {{ __('Back') }}
                        </flux:button>

                        @canAs('event.update')
                            @can('update', $event)
                                <flux:button
                                    variant="primary"
                                    size="sm"
                                    :href="route('events.edit', $event)"
                                    wire:navigate
                                    icon="pencil"
                                    class="!border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                                >
                                    {{ __('Edit Event') }}
                                </flux:button>
                            @endcan
                        @endcanAs
                    </div>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('events.index')"
                        wire:navigate
                        icon="arrow-left"
                        class="users-hero-action shrink-0"
                        :aria-label="__('Back')"
                    />

                    @canAs('event.update')
                        @can('update', $event)
                            <flux:button
                                variant="primary"
                                size="sm"
                                :href="route('events.edit', $event)"
                                wire:navigate
                                icon="pencil"
                                class="min-w-0 flex-1 !border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                            >
                                {{ __('Edit Event') }}
                            </flux:button>
                        @endcan
                    @endcanAs
                </div>
            </div>
        </div>

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4">
        <flux:tab.group>
            <flux:tabs variant="segmented" scrollable scrollable:fade>
                <flux:tab name="overview" :selected="$firstTab === 'overview'" icon="list-bullet">{{ __('Overview') }}</flux:tab>
                @canAs('event.read')
                    <flux:tab name="registrations" :selected="$firstTab === 'registrations'" icon="clipboard-document-list">{{ __('Registrations') }}</flux:tab>
                @endcanAs
                @canAs('bracket.read')
                    <flux:tab name="brackets" :selected="$firstTab === 'brackets'" icon="trophy">{{ __('Brackets') }}</flux:tab>
                @endcanAs
                @canAs('package.read')
                    <flux:tab name="packages" :selected="$firstTab === 'packages'" icon="cube">{{ __('Packages') }}</flux:tab>
                @endcanAs
                @canAs('track.read')
                    <flux:tab name="tracks" :selected="$firstTab === 'tracks'" icon="traffic-cone">{{ __('Tracks') }}</flux:tab>
                @endcanAs
                @canAs('event.update')
                    <flux:tab name="code-access" :selected="$firstTab === 'code-access'" icon="key">{{ __('Early Access') }}</flux:tab>
                @endcanAs
                @canAs('checkin.read')
                    <flux:tab name="checkin" :selected="$firstTab === 'checkin'" icon="check-badge">{{ __('Check-in') }}</flux:tab>
                @endcanAs
                @canAs('manage_live_results')
                    <flux:tab name="live-result" :selected="$firstTab === 'live-result'" icon="chart-bar">{{ __('Live Result') }}</flux:tab>
                @endcanAs
            </flux:tabs>

            <flux:tab.panel name="overview" :selected="$firstTab === 'overview'">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[30%_1fr]">
                    <div class="lg:min-w-0">
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 lg:sticky lg:top-4">
                            @if ($event->posterUrl())
                                <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}" class="w-full rounded-lg object-contain" />
                            @else
                                <div class="flex aspect-[3/4] w-full items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700/50 text-zinc-400 dark:text-zinc-500">
                                    <span class="text-sm">{{ __('No poster') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="min-w-0 space-y-6">
                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                            <flux:heading size="lg" class="mb-4">{{ $event->title }}</flux:heading>
                            <dl class="grid gap-4 sm:grid-cols-1">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</dt>
                                    <dd class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $event->isCategoryUmur() ? __('Umur') : __('Tahun') }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Start') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $event->start_at->format('l, d F Y H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('End') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $event->end_at?->format('l, d F Y H:i') ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</dt>
                                    <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">
                                        @if ($event->location)
                                            @if ($event->location->google_map && str_starts_with($event->location->google_map, 'http'))
                                                <a href="{{ $event->location->google_map }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                    {{ $event->location->name }}
                                                </a>
                                            @else
                                                {{ $event->location->name }}
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </dd>
                                </div>
                                @if ($event->racingCommittee)
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Racing committee') }}</dt>
                                        <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $event->racingCommittee->name }}</dd>
                                    </div>
                                @endif
                                @if ($event->masterOfCeremony)
                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Master of ceremony') }}</dt>
                                        <dd class="mt-1 text-sm text-zinc-700 dark:text-zinc-300">{{ $event->masterOfCeremony->name }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </div>

                        @if ($event->description)
                            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                                <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</h2>
                                <div class="mt-3 text-sm text-zinc-700 whitespace-pre-line dark:text-zinc-300">{{ trim($event->description) }}</div>
                            </div>
                        @endif

                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                            <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Brackets') }}</h2>
                            @if ($event->brackets->isNotEmpty())
                                <ul class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach ($event->brackets as $bracket)
                                        @php
                                            $quota = $bracket->quota;
                                            $remaining = $bracket->remainingQuota();
                                            // Admin overview: always show quota progress even if bracket hides it publicly.
                                            $showQuota = $quota !== null && $remaining !== null;
                                            $used = $showQuota ? max(0, $quota - $remaining) : null;
                                            $pct = $showQuota && $quota > 0 ? min(100, max(0, (int) round(($used / $quota) * 100))) : null;
                                        @endphp
                                        <li class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/20 p-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="font-medium text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $bracket->name }}</div>
                                                    @if ($showQuota)
                                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ $used }} / {{ $quota }} {{ __('slots') }} ({{ $pct }}%)
                                                        </div>
                                                    @elseif ($quota === null)
                                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Unlimited') }}</div>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col items-end gap-1 shrink-0">
                                                    @if ($showQuota)
                                                        <flux:badge color="{{ $remaining > 0 ? 'zinc' : 'red' }}" size="xs">
                                                            {{ $remaining > 0 ? $remaining . ' ' . __('left') : __('Full') }}
                                                        </flux:badge>
                                                    @endif
                                                </div>
                                            </div>

                                            @if ($showQuota)
                                                <div class="mt-3 h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                                    <div
                                                        class="h-full rounded-full {{ $remaining > 0 ? 'bg-indigo-600 dark:bg-indigo-500' : 'bg-red-600 dark:bg-red-500' }}"
                                                        style="width: {{ $pct }}%"
                                                    ></div>
                                                </div>
                                            @endif
                                                <div class="mt-3">
                                                    <flux:badge color="{{ $bracket->hide_quota ? 'amber' : 'zinc' }}" class="text-xs">
                                                        {{ $bracket->hide_quota ? __('Quota Hidden') : __('Quota Shown') }}
                                                    </flux:badge>
                                                </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No brackets for this event.') }}</p>
                            @endif
                        </div>

                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                            <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Packages') }}</h2>
                            <ul class="mt-3 space-y-3 text-sm text-zinc-700 dark:text-zinc-300">
                                @foreach ($event->packages as $pkg)
                                    <li>
                                        <span class="font-medium">{{ $pkg->name }}</span> — {{ $pkg->formatted_payable_amount }}
                                        @if ($pkg->hasAdminFee() && ! $pkg->adminFeeIsIncludedInPrice())
                                            <span class="text-zinc-500 dark:text-zinc-400"> ({{ $pkg->formatted_price }} + {{ $pkg->formatted_admin_fee }})</span>
                                        @endif
                                        @if ($pkg->quota !== null)
                                            @php $rem = $pkg->remainingQuota(); @endphp
                                            <span class="text-zinc-500 dark:text-zinc-400">({{ $rem !== null ? $rem . ' / ' . $pkg->quota . ' ' . __('slots') : __('Full') }})</span>
                                        @endif
                                        @if ($pkg->rewards->isNotEmpty())
                                            <div class="mt-1 flex flex-wrap items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                                                <flux:icon name="gift" class="size-3.5 shrink-0" />
                                                @foreach ($pkg->rewards as $reward)
                                                    <flux:badge color="zinc" size="xs">{{ $reward->name }}</flux:badge>
                                                @endforeach
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                            @if ($event->packages->isEmpty())
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No packages for this event.') }}</p>
                            @endif
                        </div>

                        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                            <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Tracks') }}</h2>
                            <ul class="mt-3 space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                                @foreach ($event->tracks as $track)
                                    <li>{{ $track->name }}@if($track->material) — {{ $track->material }}@endif@if($track->long_track) ({{ $track->long_track }})@endif</li>
                                @endforeach
                            </ul>
                            @if ($event->tracks->isEmpty())
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No tracks for this event.') }}</p>
                            @endif
                        </div>

                    </div>
                </div>
            </flux:tab.panel>

            @canAs('event.read')
                <flux:tab.panel name="registrations" :selected="$firstTab === 'registrations'">
                    <livewire:events.event-registrations-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('bracket.read')
                <flux:tab.panel name="brackets" :selected="$firstTab === 'brackets'">
                    <livewire:brackets.bracket-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('package.read')
                <flux:tab.panel name="packages" :selected="$firstTab === 'packages'">
                    <livewire:packages.package-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('track.read')
                <flux:tab.panel name="tracks" :selected="$firstTab === 'tracks'">
                    <livewire:tracks.track-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('checkin.read')
                <flux:tab.panel name="checkin" :selected="$firstTab === 'checkin'">
                    <livewire:events.event-checkin-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('manage_live_results')
                <flux:tab.panel name="live-result" :selected="$firstTab === 'live-result'">
                    <livewire:events.live-result-category-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('event.update')
                <flux:tab.panel name="code-access" :selected="$firstTab === 'code-access'">
                    <livewire:events.event-code-access-list :event="$event" />
                </flux:tab.panel>
            @endcanAs
        </flux:tab.group>
        </div>
    </div>
</x-layouts::app>

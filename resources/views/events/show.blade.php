<x-layouts::app :title="$event->title">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $event->title }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" :href="route('events.index')" wire:navigate icon="arrow-left">
                    {{ __('Back') }}
                </flux:button>
            </div>
            @canAs('event.update')
                @can('update', $event)
                    <flux:button variant="primary" :href="route('events.edit', $event)" wire:navigate icon="pencil">
                        {{ __('Edit Event') }}
                    </flux:button>
                @endcan
            @endcanAs
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[30%_1fr]">
            <div class="lg:min-w-0">
                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 lg:sticky lg:top-4">
                    @if ($event->posterUrl())
                        <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}" class="w-full rounded-lg object-contain" />
                    @else
                        <div class="flex aspect-[3/4] w-full items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700/50 text-zinc-400 dark:text-zinc-500">
                            <span class="text-sm">{{ __('No poster') }}</span>
                        </div>
                    @endif

<div>
                @canAs('event.update')
                    <flux:button variant="ghost" size="sm" :href="route('events.code-access.index', $event)" wire:navigate icon="key">
                        {{ __('Early access codes') }}
                    </flux:button>
                @endcanAs
                </div>
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
                        <div class="mt-3 text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $event->description }}</div>
                    </div>
                @endif

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Packages') }}</h2>
                        @canAs('package.read')
                            <flux:button variant="ghost" size="sm" :href="route('events.packages.index', $event)" wire:navigate icon="cube">
                                {{ __('Manage packages') }}
                            </flux:button>
                        @endcanAs
                    </div>
                    <ul class="mt-3 space-y-3 text-sm text-zinc-700 dark:text-zinc-300">
                        @foreach ($event->packages as $pkg)
                            <li>
                                <span class="font-medium">{{ $pkg->name }}</span> — {{ $pkg->formatted_price }}
                                @if ($pkg->race_pack)
                                    <span class="text-zinc-500 dark:text-zinc-400">({{ \Illuminate\Support\Str::limit($pkg->race_pack, 40) }})</span>
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
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Tracks') }}</h2>
                        @canAs('track.read')
                            <flux:button variant="ghost" size="sm" :href="route('events.tracks.index', $event)" wire:navigate icon="map-pin">
                                {{ __('Manage tracks') }}
                            </flux:button>
                        @endcanAs
                    </div>
                    <ul class="mt-3 space-y-1 text-sm text-zinc-700 dark:text-zinc-300">
                        @foreach ($event->tracks as $track)
                            <li>{{ $track->name }}@if($track->material) — {{ $track->material }}@endif@if($track->long_track) ({{ $track->long_track }})@endif</li>
                        @endforeach
                    </ul>
                    @if ($event->tracks->isEmpty())
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No tracks for this event.') }}</p>
                    @endif
                </div>

                <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Brackets') }}</h2>
                        <div class="flex flex-wrap gap-2">
                            @canAs('event.read')
                                <flux:button variant="ghost" size="sm" :href="route('events.registrations.index', $event)" wire:navigate icon="clipboard-document-list">
                                    {{ __('Registrations') }}
                                </flux:button>
                            @endcanAs
                            @canAs('bracket.read')
                                <flux:button variant="ghost" size="sm" :href="route('events.brackets.index', $event)" wire:navigate icon="trophy">
                                    {{ __('Manage brackets') }}
                                </flux:button>
                            @endcanAs
                        </div>
                    </div>
                    @if ($event->brackets->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($event->brackets as $bracket)
                                <li>
                                    <flux:badge color="zinc" size="sm">{{ $bracket->name }}</flux:badge>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No brackets for this event.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layouts::app>

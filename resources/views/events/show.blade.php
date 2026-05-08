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
        </div>

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
                    <flux:tab name="live-result" :selected="$firstTab === 'live-result'" icon="chart-bar">{{ __('Kelola Live Result') }}</flux:tab>
                @endcanAs
            </flux:tabs>

            <flux:tab.panel name="overview" :selected="$firstTab === 'overview'">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[30%_1fr]">
                    <div class="lg:min-w-0">
                        @canAs('event.update')
                            @can('update', $event)
                                <flux:button class="w-full" variant="primary" :href="route('events.edit', $event)" wire:navigate icon="pencil">
                                    {{ __('Edit Event') }}
                                </flux:button>
                            @endcan
                        @endcanAs
                        <div class="mt-4 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 lg:sticky lg:top-4">
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
                                <div class="mt-3 text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $event->description }}</div>
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
                                            $showQuota = !$bracket->hide_quota && $quota !== null && $remaining !== null;
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
                                                    @elseif (!$bracket->hide_quota && $quota === null)
                                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Unlimited') }}</div>
                                                    @endif
                                                </div>
                                                @if ($showQuota)
                                                    <flux:badge color="{{ $remaining > 0 ? 'zinc' : 'red' }}" size="xs">
                                                        {{ $remaining > 0 ? $remaining . ' ' . __('left') : __('Full') }}
                                                    </flux:badge>
                                                @endif
                                            </div>

                                            @if ($showQuota)
                                                <div class="mt-3 h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700 overflow-hidden">
                                                    <div
                                                        class="h-full rounded-full {{ $remaining > 0 ? 'bg-indigo-600 dark:bg-indigo-500' : 'bg-red-600 dark:bg-red-500' }}"
                                                        style="width: {{ $pct }}%"
                                                    ></div>
                                                </div>
                                            @endif
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
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        @canAs('bracket.create')
                            <flux:button variant="primary" :href="route('events.brackets.create', $event)" wire:navigate icon="plus">
                                {{ __('Add Bracket') }}
                            </flux:button>
                        @endcanAs
                    </div>
                    <livewire:brackets.bracket-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('package.read')
                <flux:tab.panel name="packages" :selected="$firstTab === 'packages'">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                        {{ __('Packages define registration price and race pack. If there is only one package, participants will not need to choose.') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        @canAs('package.create')
                            <flux:button variant="primary" :href="route('events.packages.create', $event)" wire:navigate icon="plus">
                                {{ __('Add Package') }}
                            </flux:button>
                        @endcanAs
                    </div>
                    <livewire:packages.package-list :event="$event" />
                </flux:tab.panel>
            @endcanAs

            @canAs('track.read')
                <flux:tab.panel name="tracks" :selected="$firstTab === 'tracks'">
                    @if (session('status'))
                        <flux:callout variant="success" class="rounded-lg mb-4">{{ session('status') }}</flux:callout>
                    @endif
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
                        {{ __('Tracks define the race circuit or route for this event (name, material, length, photo).') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2 mb-4">
                        @canAs('track.create')
                            <flux:button variant="primary" :href="route('events.tracks.create', $event)" wire:navigate icon="plus">
                                {{ __('Add Track') }}
                            </flux:button>
                        @endcanAs
                    </div>
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
                    @include('admin.live-result-categories.partials.manage', ['event' => $event, 'categories' => $categories])
                </flux:tab.panel>
            @endcanAs

            @canAs('event.update')
                <flux:tab.panel name="code-access" :selected="$firstTab === 'code-access'">
                    <flux:subheading class="mb-4">{{ __('Share these codes to allow early registration before registration opens.') }}</flux:subheading>
                    @if (session('status'))
                        <flux:callout variant="success" class="rounded-lg mb-4">{{ session('status') }}</flux:callout>
                    @endif
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-6 mb-6">
                        <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-4">{{ __('Add code') }}</h2>
                        <form method="POST" action="{{ route('events.code-access.store', $event) }}" class="max-w-xl space-y-4">
                            @csrf
                            <div class="grid gap-4 sm:grid-cols-2">
                                <flux:input name="code" type="text" :label="__('Code')" :placeholder="__('e.g. EARLY2025')" required />
                                <flux:input name="name" type="text" :label="__('Name (optional)')" :placeholder="__('e.g. Early Bird')" />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <flux:input name="valid_from" type="datetime-local" :label="__('Valid from (optional)')" />
                                <flux:input name="valid_until" type="datetime-local" :label="__('Valid until (optional)')" />
                            </div>
                            <flux:input name="usage_limit" type="number" min="1" :label="__('Usage limit (optional)')" :placeholder="__('Max uses, leave empty for unlimited')" />
                            <flux:button type="submit" variant="primary">{{ __('Add code') }}</flux:button>
                        </form>
                    </div>
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <h2 class="p-4 text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">{{ __('Existing codes') }}</h2>
                        @if ($codes->isEmpty())
                            <p class="p-6 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No access codes yet.') }}</p>
                        @else
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-800">
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Code') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Used') }}</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Valid') }}</th>
                                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                    @foreach ($codes as $ca)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-mono text-zinc-900 dark:text-zinc-100">{{ $ca->code }}</td>
                                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $ca->name ?? '—' }}</td>
                                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $ca->times_used }}@if($ca->usage_limit) / {{ $ca->usage_limit }}@endif</td>
                                            <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                                @if ($ca->valid_from || $ca->valid_until)
                                                    {{ $ca->valid_from?->format('d/m/Y H:i') ?? '—' }} → {{ $ca->valid_until?->format('d/m/Y H:i') ?? '—' }}
                                                @else
                                                    {{ __('Always') }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" action="{{ route('events.code-access.destroy', [$event, $ca]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this code?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <flux:button type="submit" variant="ghost" size="sm" color="red">{{ __('Remove') }}</flux:button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </flux:tab.panel>
            @endcanAs
        </flux:tab.group>
    </div>
</x-layouts::app>

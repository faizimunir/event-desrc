<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head', ['title' => $event->title])
    <style>
        html {
            scroll-behavior: smooth;
        }

        #registration-form,
        #registration-packages,
        #event-participants {
            scroll-margin-top: 5rem;
        }
    </style>
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @persist('toast')
        <flux:toast />
    @endpersist
    <livewire:flash-toast />
    @include('partials.navbar')

    <flux:main container class="!p-0 lg:!px-8 overflow-x-hidden">
        <div class="pt-0 pb-8 lg:py-8 space-y-6">
            <div class="hidden lg:flex lg:items-center lg:justify-between lg:gap-4 px-4 sm:px-6 lg:px-0 mb-2">
                <nav aria-label="Breadcrumb" class="min-w-0">
                    <flux:breadcrumbs class="flex flex-wrap items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>{{ __('Home') }}
                        </flux:breadcrumbs.item>
                        <flux:breadcrumbs.item href="{{ route('home') }}#events" wire:navigate>{{ __('Events') }}
                        </flux:breadcrumbs.item>
                        <flux:breadcrumbs.item
                            class="text-zinc-900 dark:text-zinc-100 truncate max-w-[12rem] sm:max-w-none">
                            {{ $event->title }}</flux:breadcrumbs.item>
                    </flux:breadcrumbs>
                </nav>
                <div class="flex items-center gap-2 shrink-0">
                    <flux:button variant="filled" size="sm" href="{{ route('live-result.show', $event->slug) }}" wire:navigate
                        icon="chart-bar" class="!bg-orange-500 hover:!bg-orange-600 focus:!ring-orange-500 dark:!bg-orange-500 dark:hover:!bg-orange-600">
                        {{ __('Live Result') }}
                    </flux:button>
                    <flux:button variant="filled" size="sm" href="{{ route('home') }}#events" wire:navigate
                        icon="arrow-left">
                        {{ __('Back') }}
                    </flux:button>
                </div>
            </div>

            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-0 grid grid-cols-1 gap-4 lg:grid-cols-[320px_minmax(0,1fr)] lg:grid-rows-2 lg:gap-4">
                {{-- Left column: poster / hero --}}
                <div class="w-full lg:min-w-0 lg:row-start-1 lg:col-start-1 lg:h-fit">
                    @if ($event->posterUrl())
                        <div
                            class="relative min-h-[55vh] w-screen ml-[calc(-50vw+50%)] bg-zinc-900 overflow-hidden lg:w-full lg:ml-0 lg:min-h-0 lg:rounded-2xl lg:border lg:border-zinc-200 dark:lg:border-zinc-700 lg:sticky">
                            <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}"
                                class="absolute inset-0 h-full w-full object-cover object-center lg:static lg:aspect-[3/4]" />

                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent lg:hidden">
                            </div>

                            <div class="absolute left-0 right-0 top-0 z-10 m-3 flex justify-between gap-2 lg:hidden">
                                <flux:button variant="primary" size="sm" href="{{ route('home') }}#events"
                                    wire:navigate icon="arrow-left">
                                    {{ __('Back') }}
                                </flux:button>
                                <flux:button variant="filled" size="sm" href="{{ route('live-result.show', $event->slug) }}"
                                    wire:navigate icon="chart-bar" class="!bg-orange-500 hover:!bg-orange-600">
                                    {{ __('Live Result') }}
                                </flux:button>
                            </div>

                            <div class="absolute inset-0 flex flex-col justify-end p-4 pb-8 lg:hidden">
                                <h1 class="text-2xl font-bold text-white drop-shadow-lg">{{ $event->title }}</h1>
                                @if ($event->location)
                                    <p class="mt-1 text-sm text-white/90 drop-shadow">{{ $event->location->name }}</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <div
                            class="min-h-[40vh] w-screen ml-[calc(-50vw+50%)] flex items-center justify-center bg-zinc-200 dark:bg-zinc-700 text-zinc-500 lg:w-full lg:ml-0 lg:min-h-0 lg:rounded-2xl lg:border lg:border-zinc-200 dark:lg:border-zinc-700 lg:aspect-[3/4]">
                            <flux:icon name="calendar" class="size-16" />
                        </div>
                    @endif
                </div>

                <div class="mt-4 lg:mt-0 lg:row-start-2 lg:col-start-1 space-y-4">
                    @if ($event->organizer)
                        <div
                            class="relative overflow-hidden flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700 mb-4">
                            <div class="absolute right-0 top-0 bottom-0 w-1/3 flex items-center justify-center pointer-events-none" aria-hidden="true">
                                <flux:icon name="building-2" class="size-16 text-zinc-300 dark:text-zinc-600 opacity-20" />
                            </div>
                            <flux:avatar :name="$event->organizer->name" :initials="$event->organizer->initials()"
                                class="relative z-10 h-12 w-12 shrink-0 text-lg" />
                            <div class="relative z-10 min-w-0 flex-1">
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    {{ __('Organizer') }}</p>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                    @if ($event->organizer->link && str_starts_with($event->organizer->link, 'http'))
                                        <a href="{{ $event->organizer->link }}" target="_blank" rel="noopener"
                                            class="hover:underline">{{ $event->organizer->name }}</a>
                                    @else
                                        {{ $event->organizer->name }}
                                    @endif
                                </p>
                                @if ($event->organizer->user)
                                    @php $orgAdmin = $event->organizer->user; @endphp
                                    <div>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                            <span
                                                class="font-medium text-zinc-500 dark:text-zinc-500">{{ __('Admin') }} :</span>
                                        </p>
                                        @if (filled($orgAdmin->whatsapp))
                                            @php
                                                $orgAdminWa = \App\Services\WhacenterService::normalizeWhatsApp(
                                                    $orgAdmin->whatsapp,
                                                );
                                            @endphp
                                            <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                                <span
                                                    class="font-medium text-zinc-500 dark:text-zinc-500">{{ $orgAdmin->name }}</span>
                                                <a href="https://wa.me/{{ $orgAdminWa }}" target="_blank" rel="noopener"
                                                    class="text-emerald-600 hover:underline dark:text-emerald-400">({{ $orgAdmin->whatsapp }})</a>
                                            </p>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                            @if ($event->racingCommittee)
                                @php $rc = $event->racingCommittee; @endphp
                                <div
                                    class="relative overflow-hidden flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700 mb-4"
                                    x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                                    <div class="absolute right-0 top-0 bottom-0 w-1/3 flex items-center justify-center pointer-events-none" aria-hidden="true">
                                        <flux:icon name="trophy" class="size-16 text-zinc-300 dark:text-zinc-600 opacity-20" />
                                    </div>
                                    @if ($rc->photo_rc_url)
                                        <button type="button" @click="previewOpen = true" class="relative z-10 shrink-0 rounded-lg overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2">
                                            <img src="{{ $rc->photo_rc_url }}" alt="{{ $rc->name }}"
                                                class="h-12 w-12 rounded-lg object-cover bg-zinc-200 dark:bg-zinc-600 cursor-pointer" />
                                        </button>
                                        <div x-show="previewOpen" x-transition.opacity x-cloak
                                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                            @click.self="previewOpen = false"
                                            role="dialog" aria-modal="true" :aria-hidden="!previewOpen">
                                            <img src="{{ $rc->photo_rc_url }}" alt="{{ $rc->name }}"
                                                class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                @click.stop />
                                        </div>
                                    @else
                                        <flux:avatar :name="$rc->name" :initials="$rc->initials()"
                                            class="relative z-10 h-12 w-12 shrink-0 text-lg" />
                                    @endif
                                    <div class="relative z-10 min-w-0 flex-1">
                                        <p
                                            class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Racing committee') }}</p>
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                            @if ($rc->link)
                                                <a href="{{ $rc->link }}" target="_blank" rel="noopener"
                                                    class="hover:underline">{{ $rc->name }}</a>
                                            @else
                                                {{ $rc->name }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif
                            @if ($event->masterOfCeremony)
                                @php $mc = $event->masterOfCeremony; @endphp
                                <div
                                    class="relative overflow-hidden flex items-center gap-3 p-3 border rounded-lg border-zinc-200 dark:border-zinc-700"
                                    x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                                    <div class="absolute right-0 top-0 bottom-0 w-1/3 flex items-center justify-center pointer-events-none" aria-hidden="true">
                                        <flux:icon name="microphone" class="size-16 text-zinc-300 dark:text-zinc-600 opacity-20" />
                                    </div>
                                    @if ($mc->avatar_mc_url)
                                        <button type="button" @click="previewOpen = true" class="relative z-10 shrink-0 rounded-lg overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2">
                                            <img src="{{ $mc->avatar_mc_url }}" alt="{{ $mc->name }}"
                                                class="h-12 w-12 rounded-lg object-cover bg-zinc-200 dark:bg-zinc-600 cursor-pointer" />
                                        </button>
                                        <div x-show="previewOpen" x-transition.opacity x-cloak
                                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                            @click.self="previewOpen = false"
                                            role="dialog" aria-modal="true" :aria-hidden="!previewOpen">
                                            <img src="{{ $mc->avatar_mc_url }}" alt="{{ $mc->name }}"
                                                class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                @click.stop />
                                        </div>
                                    @else
                                        <flux:avatar :name="$mc->name" :initials="$mc->initials()"
                                            class="relative z-10 h-12 w-12 shrink-0 text-lg" />
                                    @endif
                                    <div class="relative z-10 min-w-0 flex-1">
                                        <p
                                            class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Master of ceremony') }}</p>
                                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">
                                            @if ($mc->link)
                                                <a href="{{ $mc->link }}" target="_blank" rel="noopener"
                                                    class="hover:underline">{{ $mc->name }}</a>
                                            @else
                                                {{ $mc->name }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            @endif
                        
                </div>

                {{-- Right column: details --}}
                <div class="space-y-4 min-w-0 lg:row-span-2 lg:col-start-2">
                    <div
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                        <flux:heading size="xl" level="1" class="break-words">{{ $event->title }}
                        </flux:heading>
                        <dl class="grid gap-4">
                            <dd class="mt-2">
                                <flux:badge color="zinc" size="sm">
                                    {{ $event->isCategoryUmur() ? __('Umur') : __('Tahun') }}</flux:badge>
                            </dd>

                            <dd class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                                <flux:icon name="calendar" class="size-4 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                {{ $event->start_at->format('l, d F Y H:i') }}
                            </dd>

                            <dd class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-300">
                                <flux:icon name="map-pin" class="size-4 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                @if ($event->location)
                                    @if ($event->location->google_map && str_starts_with($event->location->google_map, 'http'))
                                        <a href="{{ $event->location->google_map }}" target="_blank" rel="noopener"
                                            class="text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $event->location->name }}
                                        </a>
                                    @else
                                        {{ $event->location->name }}
                                    @endif
                                @else
                                    —
                                @endif
                            </dd>
                            @if ($event->description)
                                <div>
                                    <dt
                                        class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        {{ __('Description') }}</dt>
                                    <dd class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">
                                        {{ $event->description }}</dd>
                                </div>
                            @endif
                        </dl>

                    </div>

                    <div
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                        <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Brackets') }}</h2>
                        @if ($event->brackets_sorted_for_display->isNotEmpty())
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($event->brackets_sorted_for_display as $bracket)
                                    @php $remaining = $bracket->remainingQuota(); @endphp
                                    <li>
                                        <flux:badge color="zinc" size="sm">
                                            {{ $bracket->name }}
                                            @if (!$bracket->hide_quota && $remaining !== null)
                                                <span
                                                    class="ml-1 text-zinc-500">({{ $remaining }}/{{ $bracket->quota }})</span>
                                            @endif
                                        </flux:badge>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No brackets for this event.') }}</p>
                        @endif
                    </div>

                    <div
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                        <h2
                            class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-4">
                            {{ __('Rewards by package') }}</h2>
                        @if ($event->packages->isNotEmpty())
                            <flux:tab.group>
                                <flux:tabs variant="segmented" class="mb-0">
                                    @foreach ($event->packages as $package)
                                        <flux:tab :name="'rewards-pkg-'.$package->id" :selected="$loop->first"
                                            >{{ $package->name }}</flux:tab>
                                    @endforeach
                                </flux:tabs>
                                @foreach ($event->packages as $package)
                                    <flux:tab.panel :name="'rewards-pkg-'.$package->id" :selected="$loop->first">
                                        @if ($package->rewards->isNotEmpty())
                                            <ul class="grid grid-cols-2 gap-2">
                                                @foreach ($package->rewards as $reward)
                                                    <li
                                                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/50 px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                                        @if ($reward->icon)
                                                            <flux:icon :name="$reward->icon"
                                                                class="size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                                        @else
                                                            <flux:icon name="gift"
                                                                class="size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />
                                                        @endif
                                                        <span>{{ $reward->name }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ __('No rewards for this package.') }}</p>
                                        @endif
                                    </flux:tab.panel>
                                @endforeach
                            </flux:tab.group>
                        @else
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Packages not announced yet.') }}</p>
                        @endif
                    </div>

                    <div
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6">
                        <h2 class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Tracks') }}</h2>
                        @if ($event->tracks->isNotEmpty())
                            <ul class="mt-3 space-y-4">
                                @foreach ($event->tracks as $track)
                                    <li
                                        class="flex gap-4 items-start rounded-lg border border-zinc-200 dark:border-zinc-600 bg-zinc-50/50 dark:bg-zinc-800/50 p-4"
                                        x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                                        @if ($track->photoTrackUrl())
                                            <button type="button"
                                                @click="previewOpen = true"
                                                class="shrink-0 rounded-lg overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2"
                                                aria-label="{{ __('View full photo of :name', ['name' => $track->name]) }}">
                                                <img src="{{ $track->photoTrackUrl() }}" alt="{{ $track->name }}"
                                                    class="h-20 w-28 object-cover bg-zinc-200 dark:bg-zinc-600 cursor-pointer hover:opacity-90 transition-opacity" />
                                            </button>
                                            <div x-show="previewOpen" x-transition.opacity x-cloak
                                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                                @click.self="previewOpen = false"
                                                role="dialog" aria-modal="true" :aria-hidden="!previewOpen">
                                                <img src="{{ $track->photoTrackUrl() }}" alt="{{ $track->name }}"
                                                    class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                    @click.stop />
                                            </div>
                                        @else
                                            <div
                                                class="h-20 w-28 shrink-0 rounded-lg bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center">
                                                <flux:icon name="map-pin"
                                                    class="size-8 text-zinc-400 dark:text-zinc-500" />
                                            </div>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $track->name }}</p>
                                            @if ($track->material || $track->long_track)
                                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                                    @if ($track->material)
                                                        Material : {{ $track->material }}
                                                    @endif
                                                    @if ($track->material && $track->long_track)
                                                        <br />
                                                    @endif
                                                    @if ($track->long_track)
                                                        Long Track : {{ $track->long_track }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Track not announced Yet') }}</p>
                        @endif
                    </div>

                </div>
            </div>

            <div class="space-y-6 px-4 sm:px-6 lg:px-0">
                @php
                    $showParticipantsPublicly = (bool) $event->show_participants_publicly;
                    $participantTabActive = $showParticipantsPublicly && request()->has('participant_page');
                    $participantRegistrations = null;
                    $participantBracketOptions = collect();
                    if ($showParticipantsPublicly) {
                        $participantRegistrations = \App\Models\Registration::query()
                            ->with(['rider.teams', 'bracket', 'package'])
                            ->where('event_id', $event->id)
                            ->where('status', \App\Models\Registration::STATUS_APPROVED)
                            ->whereHas('order', function ($orderQuery) {
                                $orderQuery->whereIn('status', [
                                    \App\Models\Order::STATUS_PAID,
                                    \App\Models\Order::STATUS_COMPLETED,
                                ])->whereHas('payments', function ($paymentQuery) {
                                    $paymentQuery->where('status', \App\Models\Payment::STATUS_SUCCESS);
                                });
                            })
                            ->latest('id')
                            ->paginate(20, ['*'], 'participant_page')
                            ->withQueryString()
                            ->fragment('event-participants');
                        $participantBracketOptions = $event->brackets_sorted_for_display
                            ->map(fn ($bracket) => [
                                'id' => (string) $bracket->id,
                                'name' => (string) $bracket->name,
                            ])
                            ->values();
                    }
                @endphp

                <flux:tab.group>
                    @if ($showParticipantsPublicly)
                        <flux:tabs variant="segmented">
                            <flux:tab icon="pencil-square" name="registration" :selected="!$participantTabActive">{{ __('Registration') }}</flux:tab>
                            <flux:tab icon="users" name="participant" :selected="$participantTabActive">{{ __('Participant') }}</flux:tab>
                        </flux:tabs>
                    @endif

                    <flux:tab.panel name="registration" :selected="! $participantTabActive">
                @if (($event->isRegistrationOpen() || $hasEarlyAccess) && $event->brackets_sorted_for_display->isNotEmpty())
                    @php
                        $showDuplicateRiderModal = session('similar_riders_choice') && session('similar_riders');
                        $similarRidersList = $showDuplicateRiderModal ? session('similar_riders') : [];
                        $oldPackageId = old('package_id');
                        $oldBracketId = old('bracket_id');
                        $selectedPackageObj = $oldPackageId
                            ? $event->packages->firstWhere('id', (int) $oldPackageId)
                            : null;
                        if ($selectedPackageObj && ! $selectedPackageObj->isActive()) {
                            $selectedPackageObj = null;
                        }
                        $selectedBracketObj = $oldBracketId
                            ? $event->brackets_sorted_for_display->firstWhere('id', (int) $oldBracketId)
                            : null;
                        $packageIdsWithJersey = $event->packages->filter(fn ($p) => $p->hasJerseyReward())->pluck('id')->map(fn ($id) => (string) $id)->values()->all();
                        $hasActivePackage = $event->packages->contains(fn ($p) => $p->isActive());
                    @endphp

                    <div x-data="{
                        selectedPackage: '{{ (string) ($selectedPackageObj?->id ?? '') }}',
                        selectedPackageLabel: '{{ addslashes((string) ($selectedPackageObj?->name ?? '')) }}',
                        selectedBracket: '{{ (string) ($selectedBracketObj?->id ?? '') }}',
                        selectedBracketLabel: '{{ addslashes((string) ($selectedBracketObj?->name ?? '')) }}',
                        requirePackage: {{ $hasActivePackage ? 'true' : 'false' }},
                        packageIdsWithJersey: {!! e(json_encode($packageIdsWithJersey)) !!},
                        scrollToForm() {
                            if (typeof window.scrollToRegistrationForm === 'function') {
                                window.scrollToRegistrationForm();
                            }
                        },
                        scrollToPackages() {
                            if (typeof window.scrollToRegistrationPackages === 'function') {
                                window.scrollToRegistrationPackages();
                            }
                        }
                    }"
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2
                                    class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                    {{ __('Registration') }}</h2>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if ($event->isEffectiveOpenRegist())
                                        {{ __('Registration open until :date', ['date' => $event->registration_closes_at?->format('d F Y H:i') ?? '—']) }}
                                    @elseif ($event->isEffectivePublished())
                                        {{ __('Early registration') }} —
                                        {{ __('Registration opens on :date', ['date' => $event->registration_opens_at?->format('d F Y H:i') ?? '—']) }}
                                    @elseif ($event->isEffectiveClosedRegist() || $event->isEffectiveDone())
                                        {{ __('Registration closed') }}
                                        @if ($event->registration_closes_at)
                                            — {{ __('Closed on :date', ['date' => $event->registration_closes_at->format('d F Y H:i')]) }}
                                        @endif
                                    @else
                                        {{ $event->effective_status_label }}
                                    @endif
                                </p>
                            </div>
                            <flux:badge variant="solid" color="{{ $event->isEffectiveOpenRegist() ? 'green' : ($event->isEffectiveDone() || $event->isEffectiveClosedRegist() ? 'zinc' : 'blue') }}" size="sm">{{ $event->isEffectiveOpenRegist() ? __('Open') : ($event->isEffectivePublished() ? __('Early access') : $event->effective_status_label) }}</flux:badge>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Select Bracket') }}</h3>
                            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                                @foreach ($event->brackets_sorted_for_display as $bracket)
                                    @php
                                        $registered = $bracket->registeredCount();
                                        $quota = $bracket->quota;
                                        $remaining = $bracket->remainingQuota();
                                        $isFull = $remaining !== null && $remaining <= 0;
                                        $showQuota = !$bracket->hide_quota && $quota !== null;
                                        $progressPct = $quota
                                            ? min(100, (int) round(($registered / max(1, $quota)) * 100))
                                            : 0;
                                    @endphp
                                    <div class="rounded-xl border p-4 transition-all"
                                        :class="selectedBracket === '{{ $bracket->id }}'
                                            ?
                                            'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/40 dark:bg-amber-900/10' :
                                            'border-zinc-200 dark:border-zinc-700'">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $bracket->name }}</div>
                                            @if ($isFull)
                                                <flux:badge color="red" size="sm">{{ __('Full') }}
                                                </flux:badge>
                                            @else
                                                <flux:badge color="zinc" size="sm">{{ __('Available') }}
                                                </flux:badge>
                                            @endif
                                        </div>

                                        @if ($showQuota)
                                            <div
                                                class="mt-3 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="h-full bg-amber-500" style="width: {{ $progressPct }}%">
                                                </div>
                                            </div>
                                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __(':used / :quota registered', ['used' => $registered, 'quota' => $quota]) }}
                                            </p>
                                        @elseif (!$bracket->hide_quota)
                                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ __('Unlimited quota') }}</p>
                                        @endif

                                        <button type="button"
                                            class="mt-3 inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium transition"
                                            :class="{{ $isFull ? "'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 cursor-not-allowed'" : "'bg-amber-600 text-white hover:bg-amber-700'" }}"
                                            @if ($isFull) disabled
                                                @else
                                                    x-on:click="selectedBracket = '{{ $bracket->id }}'; selectedBracketLabel = '{{ addslashes($bracket->name) }}'; if (requirePackage) scrollToPackages(); else scrollToForm()" @endif>
                                            {{ $isFull ? __('Full') : __('Select') }}
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($event->packages->isNotEmpty())
                            <div id="registration-packages" x-cloak x-show="selectedBracket !== ''" class="mt-6">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ __('Select Package') }}</h3>
                                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                    @foreach ($event->packages as $package)
                                        @php
                                            $pkgFull = $package->isQuotaFull();
                                            $pkgSelectable = $package->isActive() && !$pkgFull;
                                        @endphp
                                        <button type="button"
                                            @if ($pkgSelectable) x-on:click="selectedPackage = '{{ $package->id }}'; selectedPackageLabel = '{{ addslashes($package->name) }}'; scrollToForm()" @endif
                                            @if (!$pkgSelectable) disabled @endif
                                            class="relative rounded-xl border p-4 text-left transition-all"
                                            :class="selectedPackage === '{{ $package->id }}'
                                                ?
                                                'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50 dark:bg-amber-900/20' :
                                                {{ $pkgSelectable ? "'border-zinc-200 dark:border-zinc-700 hover:border-zinc-300 dark:hover:border-zinc-600'" : "'border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800/50 cursor-not-allowed opacity-75'" }}">
                                            @if (!$package->isActive())
                                                <div class="absolute right-4 top-4">
                                                    <flux:badge color="zinc" size="sm">{{ __('Coming soon') }}</flux:badge>
                                                </div>
                                            @endif
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $package->name }}
                                                </div>
                                                @if ($pkgFull)
                                                    <flux:badge color="red" size="sm">
                                                        {{ $package->isSoldOut() ? __('Sold out') : __('Booked') }}
                                                    </flux:badge>
                                                @endif
                                            </div>
                                            <div class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                                {{ $package->formatted_payable_amount }}
                                                @if ($package->hasAdminFee() && $package->adminFeeIsIncludedInPrice())
                                                    <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Includes platform admin fee :fee', ['fee' => $package->formatted_admin_fee]) }}
                                                    </span>
                                                @elseif ($package->hasAdminFee() && ! $package->adminFeeIsIncludedInPrice())
                                                    <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Registration :reg + admin :adm', ['reg' => $package->formatted_price, 'adm' => $package->formatted_admin_fee]) }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if (!$package->hide_quota && $package->quota !== null)
                                                @php $rem = $package->remainingQuota(); @endphp
                                                <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $rem !== null ? __(':remaining of :quota slots', ['remaining' => $rem, 'quota' => $package->quota]) : __('Full') }}
                                                </div>
                                            @endif
                                            @if ($package->rewards->isNotEmpty())
                                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                    @foreach ($package->rewards as $reward)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-md bg-zinc-100 dark:bg-zinc-700/80 px-1.5 py-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                            @if ($reward->icon && (str_starts_with($reward->icon, 'http') || str_starts_with($reward->icon, '/')))
                                                                <img src="{{ $reward->icon }}" alt=""
                                                                    class="size-3.5 object-contain" />
                                                            @elseif ($reward->icon)
                                                                <flux:icon :name="$reward->icon"
                                                                    class="size-3.5 shrink-0" />
                                                            @else
                                                                <flux:icon name="gift" class="size-3.5 shrink-0" />
                                                            @endif
                                                            {{ $reward->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Jika bracket sudah dipilih tapi event tidak punya package: tampilkan pesan, jangan form --}}
                        <div x-cloak x-show="selectedBracket !== '' && !requirePackage"
                            class="mt-6 rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 text-center">
                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                                {{ __('Package Belum Tersedia') }}</p>
                        </div>

                        {{-- Form registrasi rider tampil hanya saat bracket dan package (jika ada) sudah dipilih --}}
                        <div id="registration-form" x-cloak
                            x-show="selectedBracket !== '' && (requirePackage && selectedPackage !== '')"
                            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100" class="mt-6 scroll-mt-6" style="display: none;">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ __('Data Registration') }}</h3>
                            <div
                                class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/30 p-5 sm:p-6 mt-3">

                                <form method="POST" action="{{ route('registrations.store', $event) }}"
                                     id="registration-form-submit" x-ref="regForm" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="package_id" x-bind:value="selectedPackage">
                                    <input type="hidden" name="bracket_id" x-bind:value="selectedBracket">
                                    <input type="hidden" name="use_rider_id" value=""
                                        id="input-use-rider-id">

                                    <div class="space-y-5">
                                        @if ($errors->any())
                                            <div
                                                class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-4">
                                                <ul
                                                    class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-1">
                                                    @foreach ($errors->all() as $err)
                                                        <li>{{ $err }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div
                                            class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-3 text-sm text-zinc-600 dark:text-zinc-300">
                                            <div><span class="font-medium">{{ __('Bracket') }}:</span> <span
                                                    x-text="selectedBracketLabel || '—'"></span></div>
                                            @if ($event->packages->isNotEmpty())
                                                <div class="mt-1"><span
                                                        class="font-medium">{{ __('Package') }}:</span> <span
                                                        x-text="selectedPackageLabel || '—'"></span></div>
                                            @endif
                                        </div>

                                        <hr class="border-zinc-200 dark:border-zinc-700" />
                                        <h3
                                            class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Parent / Guardian') }}</h3>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <flux:input name="parent_name" type="text"
                                                :label="__('Parent / Guardian name')" :value="old('parent_name')"
                                                required />
                                            <flux:input name="whatsapp" type="tel" :label="__('WhatsApp number')"
                                                :value="old('whatsapp')" :placeholder="__('e.g. 08123456789')"
                                                required />
                                        </div>

                                        <hr class="border-zinc-200 dark:border-zinc-700" />
                                        <h3
                                            class="text-sm font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Rider data') }}</h3>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                            <flux:input name="name" type="text" :label="__('Full name')"
                                                :value="old('name')" required />
                                            <flux:input name="nickname" type="text" :label="__('Nickname')"
                                                :value="old('nickname')" required />
                                            </div>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                            <flux:input name="pob" type="text" :label="__('Place of birth')"
                                                :value="old('pob')" required />
                                            <flux:input name="dob" type="date" :label="__('Date of birth')"
                                                :value="old('dob')" required />
                                            </div>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <flux:select name="gender" :placeholder="__('— Select —')" :label="__('Gender')" required>
                                                    <option value="boys" @selected(old('gender') === 'boys')>
                                                        {{ __('Boys') }}</option>
                                                    <option value="girls" @selected(old('gender') === 'girls')>
                                                        {{ __('Girls') }}</option>
                                                </flux:select>
                                                <flux:input name="number_plate" type="text"
                                                    :label="__('Number plate')" :value="old('number_plate')" required />
                                            </div>
                                            {{-- Jersey size: tampil hanya jika paket yang dipilih punya reward jersey --}}
                                            <div x-cloak x-show="packageIdsWithJersey.includes(selectedPackage)"
                                                class="space-y-2 sm:col-span-2"
                                                x-data="{ sizeChartPreviewOpen: false }"
                                                @keydown.escape.window="sizeChartPreviewOpen = false">
                                                <flux:select name="jersey_size" :placeholder="__('— Select size —')" :label="__('Jersey size')"
                                                    x-bind:required="packageIdsWithJersey.includes(selectedPackage)">
                                                    <option value="S" @selected(old('jersey_size') === 'S')>S</option>
                                                    <option value="M" @selected(old('jersey_size') === 'M')>M</option>
                                                    <option value="L" @selected(old('jersey_size') === 'L')>L</option>
                                                    <option value="XL" @selected(old('jersey_size') === 'XL')>XL</option>
                                                </flux:select>
                                                @if ($event->sizeChartUrl())
                                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                        <button type="button"
                                                            @click="sizeChartPreviewOpen = true"
                                                            class="underline hover:text-zinc-700 dark:hover:text-zinc-300">
                                                            {{ __('View size chart') }}
                                                        </button>
                                                    </p>
                                                    <div x-show="sizeChartPreviewOpen" x-transition.opacity x-cloak
                                                        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                                        @click.self="sizeChartPreviewOpen = false"
                                                        role="dialog" aria-modal="true" :aria-hidden="!sizeChartPreviewOpen">
                                                        <img src="{{ $event->sizeChartUrl() }}" alt="{{ __('Size chart') }}"
                                                            class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                            @click.stop />
                                                    </div>
                                                @endif
                                            </div>
                                            <livewire:team-pillbox-field />

                                            {{-- Photo KIA (Kartu Identitas Anak) --}}
                                            <div class="sm:col-span-2">
                                                <flux:label class="mb-2 block">{{ __('Photo KIA (Kartu Identitas Anak)') }}</flux:label>
                                                <input type="file" name="photo_kia" id="photo_kia" accept="image/jpeg,image/png,image/webp"
                                                    class="block w-full text-sm text-zinc-500 file:mr-4 file:rounded file:border-0 file:bg-zinc-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-zinc-700 dark:file:bg-zinc-700 dark:file:text-zinc-300" required/>
                                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ __('JPG, PNG, WebP up to :max KB', ['max' => config('media.max_upload_size_kb', 2048)]) }}
                                                </p>
                                                @error('photo_kia')
                                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                @enderror
                                            </div>

                                        <div class="pt-2">
                                            <flux:button type="submit" variant="primary" icon="pencil"
                                                x-bind:disabled="selectedBracket === '' || (requirePackage &&
                                                    selectedPackage === '')">
                                                {{ __('Submit registration') }}
                                            </flux:button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        @if ($showDuplicateRiderModal && count($similarRidersList) > 0)
                            <div x-data x-init="$nextTick(() => $dispatch('modal-show', { name: 'duplicate-rider-modal' }))"></div>
                            <flux:modal name="duplicate-rider-modal" focusable class="max-w-2xl" dismissible>
                                <div class="space-y-4">
                                    <div>
                                        <flux:heading size="lg">{{ __('You may already be registered') }}
                                        </flux:heading>
                                        <flux:subheading class="mt-1">
                                            {{ __('We found a rider with similar data for this WhatsApp number. Compare below and use the existing profile to continue.') }}
                                        </flux:subheading>
                                    </div>
                                    <ul class="space-y-4">
                                        @foreach ($similarRidersList as $sr)
                                            <li class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                                    <span
                                                        class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-0.5 text-sm font-medium text-amber-800 dark:text-amber-200">
                                                        {{ __('Similarity') }}: {{ $sr['score'] }}%
                                                    </span>
                                                    <flux:button type="button" variant="primary" size="sm"
                                                        x-on:click="document.getElementById('input-use-rider-id').value = '{{ $sr['id'] }}'; document.getElementById('registration-form-submit').submit()">
                                                        {{ __('Use this profile') }}
                                                    </flux:button>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                                    <div
                                                        class="rounded-lg bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 p-3">
                                                        <div class="font-medium text-blue-800 dark:text-blue-200 mb-2">
                                                            {{ __('New data (from form)') }}</div>
                                                        <dl class="space-y-1.5 text-zinc-700 dark:text-zinc-300">
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Full name') }}:</span>
                                                                {{ old('name', '—') }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Nickname') }}:</span>
                                                                {{ old('nickname') ?: '—' }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Place of birth') }}:</span>
                                                                {{ old('pob') ?: '—' }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Date of birth') }}:</span>
                                                                {{ old('dob', '—') }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}:</span>
                                                                {{ match (old('gender')) {'boys' => __('Boys'),'girls' => __('Girls'),'other' => __('Other'),default => old('gender') ?: '—'} }}
                                                            </div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}:</span>
                                                                {{ old('number_plate') ?: '—' }}</div>
                                                        </dl>
                                                    </div>
                                                    <div
                                                        class="rounded-lg bg-zinc-100/80 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-600 p-3">
                                                        <div class="font-medium text-zinc-800 dark:text-zinc-200 mb-2">
                                                            {{ __('Existing profile') }}</div>
                                                        <dl class="space-y-1.5 text-zinc-700 dark:text-zinc-300">
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Full name') }}:</span>
                                                                {{ $sr['name'] ?: '—' }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Nickname') }}:</span>
                                                                {{ $sr['nickname'] ?: '—' }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Place of birth') }}:</span>
                                                                {{ $sr['pob'] ?: '—' }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Date of birth') }}:</span>
                                                                {{ $sr['dob'] ?: '—' }}</div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}:</span>
                                                                {{ $sr['gender_label'] ?? ($sr['gender'] ?? '—') }}
                                                            </div>
                                                            <div><span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ __('Number plate') }}:</span>
                                                                {{ $sr['number_plate'] ?: '—' }}</div>
                                                        </dl>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </flux:modal>
                        @endif
                    </div>
                @elseif (!$event->isRegistrationOpen() && $event->registration_opens_at)
                    <div
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-5 sm:p-6 space-y-4">
                        @if (session('error'))
                            <div
                                class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                                {{ session('error') }}</div>
                        @endif
                        <div class="rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4"
                            x-data="{
                                target: new Date('{{ $event->registration_opens_at->toIso8601String() }}'),
                                now: new Date(),
                                get diff() {
                                    const d = Math.max(0, this.target - this.now);
                                    return {
                                        days: Math.floor(d / 864e5),
                                        hours: Math.floor((d % 864e5) / 36e5),
                                        minutes: Math.floor((d % 36e5) / 6e4),
                                        seconds: Math.floor((d % 6e4) / 1e3)
                                    };
                                }
                            }" x-init="setInterval(() => { now = new Date() }, 1000)">
                            <p class="text-sm text-amber-800 dark:text-amber-200">
                                @if (now()->lt($event->registration_opens_at))
                                    {{ __('Registration opens on :date', ['date' => $event->registration_opens_at->format('d F Y H:i')]) }}
                                @else
                                    {{ __('Registration is closed.') }}
                                @endif
                            </p>
                            @if (now()->lt($event->registration_opens_at))
                                <p class="mt-2 text-sm font-medium text-amber-900 dark:text-amber-100"
                                    x-show="target > now">
                                    <span
                                        x-text="String(diff.days).padStart(2,'0') + 'd ' + String(diff.hours).padStart(2,'0') + 'h ' + String(diff.minutes).padStart(2,'0') + 'm ' + String(diff.seconds).padStart(2,'0') + 's'"></span>
                                </p>
                            @endif
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Have an early access code?') }}
                        </p>
                        <flux:button type="button" variant="primary" size="sm" x-data
                            x-on:click="$dispatch('modal-show', { name: 'early-access-modal' })">
                            {{ __('Early Registration') }}
                        </flux:button>
                    </div>
                @else
                    <div
                        class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-5 sm:p-6 space-y-4">
                        @if (session('error'))
                            <div
                                class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                                {{ session('error') }}</div>
                        @endif
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">
                            {{ __('Registration is not open for this event.') }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ __('Have an early access code?') }}
                        </p>
                        <flux:button type="button" variant="primary" size="sm" x-data
                            x-on:click="$dispatch('modal-show', { name: 'early-access-modal' })">
                            {{ __('Early Registration') }}
                        </flux:button>
                    </div>
                @endif
                    </flux:tab.panel>

                    @if ($showParticipantsPublicly)
                    <flux:tab.panel name="participant" :selected="$participantTabActive">
                        <div
                            class="rounded-2xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 overflow-hidden"
                            x-data="{ search: '', selectedBracket: '' }">
                            <div class="p-5 sm:p-6">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Participant') }}
                                        </h2>
                                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ __('Riders with confirmed registration.') }}
                                        </p>
                                    </div>
                                    <flux:badge variant="solid" color="zinc" size="sm">
                                        {{ $participantRegistrations->total() }} {{ __('Rider') }}
                                    </flux:badge>
                                </div>

                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <flux:input type="search" :label="__('Search')"
                                        :placeholder="__('Search rider name, nickname, team, or number plate…')"
                                        x-model.debounce.300ms="search" />
                                    <flux:select :label="__('Filter bracket')" x-model="selectedBracket">
                                        <option value="">{{ __('All brackets') }}</option>
                                        @foreach ($participantBracketOptions as $participantBracketOption)
                                            <option value="{{ $participantBracketOption['id'] }}">
                                                {{ $participantBracketOption['name'] }}
                                            </option>
                                        @endforeach
                                    </flux:select>
                                </div>
                            </div>

                            <div id="event-participants" class="overflow-x-auto border-t border-zinc-200 dark:border-zinc-700">
                                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Rider') }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Team') }}</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Bracket') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                        @forelse ($participantRegistrations as $participantRegistration)
                                            @php
                                                $participantSearchText = mb_strtolower(trim(implode(' ', [
                                                    (string) ($participantRegistration->rider?->name ?? ''),
                                                    (string) ($participantRegistration->rider?->nickname ?? ''),
                                                    (string) ($participantRegistration->number_plate ?? ''),
                                                    (string) ($participantRegistration->rider?->number_plate ?? ''),
                                                    (string) ($participantRegistration->rider?->teams->pluck('name')->implode(' ') ?? ''),
                                                ])));
                                            @endphp
                                            <tr
                                                x-show="(selectedBracket === '' || selectedBracket === '{{ (string) $participantRegistration->bracket_id }}') && (search.trim() === '' || '{{ addslashes($participantSearchText) }}'.includes(search.trim().toLowerCase()))">
                                                <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $participantRegistration->rider?->name ?? '—' }}
                                                    <span class="text-zinc-500 dark:text-zinc-400 block">
                                                        {{ $participantRegistration->rider?->nickname ?? '—' }} ({{ $participantRegistration->rider?->number_plate ?? '—' }})
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                                    {{ $participantRegistration->rider?->teams->pluck('name')->implode(', ') ?? '—' }}
                                                </td>
                                                <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">
                                                    {{ $participantRegistration->bracket?->name ?? '—' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ __('No participants yet.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($participantRegistrations->hasPages())
                                <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-4 sm:px-6">
                                    {{ $participantRegistrations->links() }}
                                </div>
                            @endif
                        </div>
                    </flux:tab.panel>
                    @endif
                </flux:tab.group>

                {{-- Modal: input access code for early registration (shown when registration not open) --}}
                @if (!$event->isRegistrationOpen())
                    <flux:modal name="early-access-modal" focusable class="max-w-md" dismissible>
                        <form method="POST" action="{{ route('events.early-access.verify', $event->slug) }}"
                            class="space-y-4">
                            @csrf
                            <div>
                                <flux:heading size="lg">{{ __('Early Registration') }}</flux:heading>
                                <flux:subheading class="mt-1">
                                    {{ __('Enter your access code to register before registration opens.') }}
                                </flux:subheading>
                            </div>
                            @if (session('error'))
                                <div
                                    class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                                    {{ session('error') }}
                                </div>
                            @endif
                            <flux:input type="text" name="code" :label="__('Access code')"
                                :placeholder="__('Enter code')" autofocus autocomplete="off" />
                            <div class="flex justify-end gap-2">
                                <flux:button type="button" variant="ghost"
                                    x-on:click="$dispatch('modal-close', { name: 'early-access-modal' })">
                                    {{ __('Cancel') }}</flux:button>
                                <flux:button type="submit" variant="primary">{{ __('Continue') }}</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @endif

            </div>
        </div>
    </flux:main>

    @include('partials.footer')

    <script>
        function scrollToId(id) {
            var attempts = 0;
            var maxAttempts = 30;
            var idInterval = setInterval(function() {
                attempts++;
                var el = document.getElementById(id);
                var isVisible = el && el.offsetParent !== null && el.getBoundingClientRect().height > 0;
                if (isVisible) {
                    clearInterval(idInterval);
                    var y = el.getBoundingClientRect().top + window.pageYOffset;
                    var offset = 80;
                    window.scrollTo({
                        top: Math.max(0, y - offset),
                        left: 0,
                        behavior: 'smooth'
                    });
                    return;
                }
                if (attempts >= maxAttempts) clearInterval(idInterval);
            }, 100);
        }
        window.scrollToRegistrationPackages = function() {
            scrollToId('registration-packages');
        };
        window.scrollToRegistrationForm = function() {
            scrollToId('registration-form');
        };
    </script>
    @fluxScripts
</body>

</html>

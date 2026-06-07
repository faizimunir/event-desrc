<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    @include('partials.head', ['title' => $event->title])
    <style>
        html {
            scroll-behavior: smooth;
        }

        #registration-form,
        #registration-packages,
        #event-participants {
            scroll-margin-top: 6rem;
        }
    </style>
</head>

<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 antialiased">
    @persist('toast')
        <flux:toast />
    @endpersist
    <livewire:flash-toast />
    @include('partials.navbar')

    <flux:main container class="!p-0 overflow-x-hidden">
        <div class="pb-10 lg:pb-12">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="mb-6 mt-4 hidden items-center justify-between gap-4 rounded-2xl border border-zinc-200/80 bg-white px-5 py-3.5 shadow-sm dark:border-zinc-700/80 dark:bg-zinc-900 lg:flex">
                    <nav aria-label="Breadcrumb" class="min-w-0">
                        <flux:breadcrumbs
                            class="flex flex-wrap items-center gap-1 text-sm text-zinc-600 dark:text-zinc-400">
                            <flux:breadcrumbs.item href="{{ route('home') }}" wire:navigate>{{ __('Home') }}
                            </flux:breadcrumbs.item>
                            <flux:breadcrumbs.item href="{{ route('home') }}#events" wire:navigate>{{ __('Events') }}
                            </flux:breadcrumbs.item>
                            <flux:breadcrumbs.item
                                class="truncate text-zinc-900 dark:text-zinc-100 max-w-[12rem] sm:max-w-none">
                                {{ $event->title }}</flux:breadcrumbs.item>
                        </flux:breadcrumbs>
                    </nav>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($event->has_live_result)
                            <flux:button variant="filled" size="sm"
                                href="{{ route('live-result.show', $event->slug) }}" wire:navigate icon="chart-bar"
                                class="!bg-red-600 hover:!bg-red-500 focus:!ring-red-500 dark:!bg-red-600 dark:hover:!bg-red-500">
                                {{ __('Live Result') }}
                            </flux:button>
                        @endif
                        <flux:button variant="ghost" size="sm" href="{{ route('home') }}#events" wire:navigate
                            icon="arrow-left">
                            {{ __('Back') }}
                        </flux:button>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[300px_minmax(0,1fr)] lg:gap-x-8 lg:gap-y-8">
                    {{-- 1: Left sidebar (poster + team) --}}
                    <div class="space-y-4 lg:col-start-1 lg:row-start-1">
                        {{-- Poster --}}
                        <div class="w-full lg:min-w-0 lg:h-fit">
                            @if ($event->posterUrl())
                                <div
                                    class="relative min-h-[60svh] w-screen ml-[calc(-50vw+50%)] overflow-hidden bg-zinc-900 lg:sticky lg:top-20 lg:w-full lg:ml-0 lg:min-h-0 lg:rounded-2xl lg:border lg:border-zinc-200/80 lg:shadow-lg dark:lg:border-zinc-700/80">
                                    <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}"
                                        class="absolute inset-0 h-full w-full object-cover object-center lg:static lg:aspect-[3/4]" />

                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-zinc-950/90 via-zinc-950/40 to-transparent lg:hidden">
                                    </div>

                                    <div
                                        class="absolute left-0 right-0 top-0 z-10 m-3 flex justify-between gap-2 lg:hidden">
                                        <flux:button variant="ghost" size="sm" href="{{ route('home') }}#events"
                                            wire:navigate icon="arrow-left"
                                            class="!bg-black/30 !text-white backdrop-blur-md hover:!bg-black/50">
                                            {{ __('Back') }}
                                        </flux:button>
                                        @if ($event->has_live_result)
                                            <flux:button variant="filled" size="sm"
                                                href="{{ route('live-result.show', $event->slug) }}" wire:navigate
                                                icon="chart-bar" class="!bg-red-600 hover:!bg-red-500">
                                                {{ __('Live Result') }}
                                            </flux:button>
                                        @endif
                                    </div>

                                    <div class="absolute left-4 right-4 top-14 z-10 lg:hidden">
                                        <flux:badge variant="solid"
                                            color="{{ $event->isEffectiveOpenRegist() ? 'green' : ($event->isEffectiveLive() ? 'red' : ($event->isEffectiveDone() ? 'zinc' : 'blue')) }}"
                                            size="sm">{{ $event->effective_status_label }}</flux:badge>
                                    </div>

                                    <div class="absolute inset-0 flex flex-col justify-end p-5 pb-10 lg:hidden">
                                        <h1 class="text-3xl font-bold tracking-tight text-white drop-shadow-lg">
                                            {{ $event->title }}</h1>
                                        @if ($event->location)
                                            <p class="mt-2 flex items-center gap-1.5 text-sm text-white/90 drop-shadow">
                                                <flux:icon name="map-pin" variant="mini" class="size-4 shrink-0" />
                                                {{ $event->location->name }}
                                            </p>
                                        @endif
                                        <p class="mt-2 flex items-center gap-1.5 text-sm text-white/80">
                                            <flux:icon name="calendar-days" variant="mini" class="size-4 shrink-0" />
                                            {{ $event->start_at->format('d M Y, H:i') }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div
                                    class="flex min-h-[40vh] w-screen ml-[calc(-50vw+50%)] items-center justify-center bg-zinc-200 text-zinc-500 dark:bg-zinc-800 lg:w-full lg:ml-0 lg:min-h-0 lg:rounded-2xl lg:border lg:border-zinc-200/80 lg:aspect-[3/4] lg:shadow-lg dark:lg:border-zinc-700/80">
                                    <flux:icon name="calendar" class="size-16" />
                                </div>
                            @endif
                        </div>

                        {{-- Person cards --}}
                        <div class="space-y-4">
                            @if ($event->organizer)
                                <div class="event-person-card mb-4">
                                    <div class="absolute right-0 top-0 bottom-0 w-1/3 flex items-center justify-center pointer-events-none"
                                        aria-hidden="true">
                                        <flux:icon name="building-2"
                                            class="size-16 text-zinc-300 dark:text-zinc-600 opacity-20" />
                                    </div>
                                    <flux:avatar :name="$event->organizer->name"
                                        :initials="$event->organizer->initials()"
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
                                                        class="font-medium text-zinc-500 dark:text-zinc-500">{{ __('Admin') }}
                                                        :</span>
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
                                                        <a href="https://wa.me/{{ $orgAdminWa }}" target="_blank"
                                                            rel="noopener"
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
                                <div class="event-person-card mb-4" x-data="{ previewOpen: false }"
                                    @keydown.escape.window="previewOpen = false">
                                    <div class="absolute right-0 top-0 bottom-0 w-1/3 flex items-center justify-center pointer-events-none"
                                        aria-hidden="true">
                                        <flux:icon name="trophy"
                                            class="size-16 text-zinc-300 dark:text-zinc-600 opacity-20" />
                                    </div>
                                    @if ($rc->photo_rc_url)
                                        <button type="button" @click="previewOpen = true"
                                            class="relative z-10 shrink-0 rounded-lg overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2">
                                            <img src="{{ $rc->photo_rc_url }}" alt="{{ $rc->name }}"
                                                class="h-12 w-12 rounded-lg object-cover bg-zinc-200 dark:bg-zinc-600 cursor-pointer" />
                                        </button>
                                        <div x-show="previewOpen" x-transition.opacity x-cloak
                                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                            @click.self="previewOpen = false" role="dialog" aria-modal="true"
                                            :aria-hidden="!previewOpen">
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
                                <div class="event-person-card" x-data="{ previewOpen: false }"
                                    @keydown.escape.window="previewOpen = false">
                                    <div class="absolute right-0 top-0 bottom-0 w-1/3 flex items-center justify-center pointer-events-none"
                                        aria-hidden="true">
                                        <flux:icon name="microphone"
                                            class="size-16 text-zinc-300 dark:text-zinc-600 opacity-20" />
                                    </div>
                                    @if ($mc->avatar_mc_url)
                                        <button type="button" @click="previewOpen = true"
                                            class="relative z-10 shrink-0 rounded-lg overflow-hidden focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2">
                                            <img src="{{ $mc->avatar_mc_url }}" alt="{{ $mc->name }}"
                                                class="h-12 w-12 rounded-lg object-cover bg-zinc-200 dark:bg-zinc-600 cursor-pointer" />
                                        </button>
                                        <div x-show="previewOpen" x-transition.opacity x-cloak
                                            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                            @click.self="previewOpen = false" role="dialog" aria-modal="true"
                                            :aria-hidden="!previewOpen">
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
                    </div>

                    {{-- 2: Right — detail, brackets, rewards, tracks --}}
                    <div class="min-w-0 space-y-6 lg:col-start-2 lg:row-start-1">
                        <div class="event-section-card">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <flux:badge variant="solid"
                                    color="{{ $event->isEffectiveOpenRegist() ? 'green' : ($event->isEffectiveLive() ? 'red' : ($event->isEffectiveDone() ? 'zinc' : 'blue')) }}"
                                    size="sm">{{ $event->effective_status_label }}</flux:badge>
                                <flux:badge color="zinc" size="sm">
                                    {{ $event->isCategoryUmur() ? __('Umur') : __('Tahun') }}
                                </flux:badge>
                            </div>

                            <h1
                                class="mt-4 text-2xl font-bold tracking-tight text-zinc-900 break-words dark:text-white sm:text-3xl">
                                {{ $event->title }}
                            </h1>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="event-meta-item sm:col-span-2">
                                    <div class="event-meta-icon">
                                        <flux:icon name="calendar-days" class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Date & time') }}</p>
                                        <p class="mt-0.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            {{ $event->start_at->format('l, d F Y H:i') }}</p>
                                    </div>
                                </div>

                                <div class="event-meta-item sm:col-span-2">
                                    <div class="event-meta-icon">
                                        <flux:icon name="map-pin" class="size-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                            {{ __('Location') }}</p>
                                        <p class="mt-0.5 text-sm font-medium text-zinc-800 dark:text-zinc-200">
                                            @if ($event->location)
                                                @if ($event->location->google_map && str_starts_with($event->location->google_map, 'http'))
                                                    <a href="{{ $event->location->google_map }}" target="_blank"
                                                        rel="noopener"
                                                        class="text-orange-600 hover:underline dark:text-orange-400">
                                                        {{ $event->location->name }}
                                                    </a>
                                                @else
                                                    {{ $event->location->name }}
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>

                            @if ($event->description)
                                <div
                                    class="mt-5 rounded-xl border border-zinc-100 bg-zinc-50/80 p-4 dark:border-zinc-700/60 dark:bg-zinc-800/40">
                                    <p
                                        class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        {{ __('Description') }}</p>
                                    <p
                                        class="mt-2 text-sm leading-relaxed text-zinc-700 whitespace-pre-wrap dark:text-zinc-300">
                                        {{ $event->description }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="event-section-card">
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Brackets') }}</h2>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Kategori usia/tahun yang tersedia.') }}</p>
                            @if ($event->brackets_sorted_for_display->isNotEmpty())
                                <ul class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($event->brackets_sorted_for_display as $bracket)
                                        @php $remaining = $bracket->remainingQuota(); @endphp
                                        <li>
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-300">
                                                {{ $bracket->name }}
                                                @if (!$bracket->hide_quota && $remaining !== null)
                                                    <span
                                                        class="text-xs text-zinc-500 dark:text-zinc-400">{{ $remaining }}/{{ $bracket->quota }}</span>
                                                @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('No brackets for this event.') }}</p>
                            @endif
                        </div>

                        <div class="event-section-card">
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">
                                {{ __('Rewards by package') }}</h2>
                            <p class="mt-1 mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Hadiah yang didapat per paket pendaftaran.') }}</p>
                            @if ($event->packages->isNotEmpty())
                                <flux:tab.group>
                                    <flux:tabs variant="segmented" class="mb-0">
                                        @foreach ($event->packages as $package)
                                            <flux:tab :name="'rewards-pkg-'.$package->id" :selected="$loop->first">
                                                {{ $package->name }}</flux:tab>
                                        @endforeach
                                    </flux:tabs>
                                    @foreach ($event->packages as $package)
                                        <flux:tab.panel :name="'rewards-pkg-'.$package->id" :selected="$loop->first">
                                            @if ($package->rewards->isNotEmpty())
                                                <ul class="grid grid-cols-2 gap-2">
                                                    @foreach ($package->rewards as $reward)
                                                        <li
                                                            class="inline-flex items-center gap-2 rounded-xl border border-zinc-200/80 bg-zinc-50/80 px-3 py-2.5 text-sm text-zinc-700 transition hover:border-orange-200 hover:bg-orange-50/50 dark:border-zinc-600 dark:bg-zinc-800/50 dark:text-zinc-300 dark:hover:border-orange-800 dark:hover:bg-orange-950/20">
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

                        <div class="event-section-card">
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Tracks') }}</h2>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Lintasan yang digunakan pada event ini.') }}</p>
                            @if ($event->tracks->isNotEmpty())
                                <ul class="mt-3 space-y-4">
                                    @foreach ($event->tracks as $track)
                                        <li class="flex items-start gap-4 rounded-2xl border border-zinc-200/80 bg-zinc-50/80 p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-600 dark:bg-zinc-800/50 dark:hover:border-zinc-500"
                                            x-data="{ previewOpen: false }" @keydown.escape.window="previewOpen = false">
                                            @if ($track->photoTrackUrl())
                                                <button type="button" @click="previewOpen = true"
                                                    class="shrink-0 overflow-hidden rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2"
                                                    aria-label="{{ __('View full photo of :name', ['name' => $track->name]) }}">
                                                    <img src="{{ $track->photoTrackUrl() }}"
                                                        alt="{{ $track->name }}"
                                                        class="h-20 w-28 cursor-pointer object-cover bg-zinc-200 transition duration-300 hover:scale-105 dark:bg-zinc-600" />
                                                </button>
                                                <div x-show="previewOpen" x-transition.opacity x-cloak
                                                    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                                    @click.self="previewOpen = false" role="dialog"
                                                    aria-modal="true" :aria-hidden="!previewOpen">
                                                    <img src="{{ $track->photoTrackUrl() }}"
                                                        alt="{{ $track->name }}"
                                                        class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                        @click.stop />
                                                </div>
                                            @else
                                                <div
                                                    class="flex h-20 w-28 shrink-0 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-600">
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

                {{-- 3: Registration — full width --}}
                <div class="mt-8 space-y-6 lg:mt-10">
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
                                    $orderQuery
                                        ->whereIn('status', [
                                            \App\Models\Order::STATUS_PAID,
                                            \App\Models\Order::STATUS_COMPLETED,
                                        ])
                                        ->whereHas('payments', function ($paymentQuery) {
                                            $paymentQuery->where('status', \App\Models\Payment::STATUS_SUCCESS);
                                        });
                                })
                                ->latest('id')
                                ->paginate(20, ['*'], 'participant_page')
                                ->withQueryString()
                                ->fragment('event-participants');
                            $participantBracketOptions = $event->brackets_sorted_for_display
                                ->map(
                                    fn($bracket) => [
                                        'id' => (string) $bracket->id,
                                        'name' => (string) $bracket->name,
                                    ],
                                )
                                ->values();
                        }
                    @endphp

                    <flux:tab.group>
                        @if ($showParticipantsPublicly)
                            <flux:tabs variant="segmented">
                                <flux:tab icon="pencil-square" name="registration"
                                    :selected="!$participantTabActive">{{ __('Registration') }}</flux:tab>
                                <flux:tab icon="users" name="participant" :selected="$participantTabActive">
                                    {{ __('Participant') }}</flux:tab>
                            </flux:tabs>
                        @endif

                        <flux:tab.panel name="registration" :selected="!$participantTabActive">
                            @if (($event->isRegistrationOpen() || $hasEarlyAccess) && $event->brackets_sorted_for_display->isNotEmpty())
                                @php
                                    $showDuplicateRiderModal =
                                        session('similar_riders_choice') && session('similar_riders');
                                    $similarRidersList = $showDuplicateRiderModal ? session('similar_riders') : [];
                                    $oldPackageId = old('package_id');
                                    $oldBracketId = old('bracket_id');
                                    $selectedPackageObj = $oldPackageId
                                        ? $event->packages->firstWhere('id', (int) $oldPackageId)
                                        : null;
                                    if ($selectedPackageObj && !$selectedPackageObj->isActive()) {
                                        $selectedPackageObj = null;
                                    }
                                    $selectedBracketObj = $oldBracketId
                                        ? $event->brackets_sorted_for_display->firstWhere('id', (int) $oldBracketId)
                                        : null;
                                    $packageIdsWithJersey = $event->packages
                                        ->filter(fn($p) => $p->hasJerseyReward())
                                        ->pluck('id')
                                        ->map(fn($id) => (string) $id)
                                        ->values()
                                        ->all();
                                    $hasActivePackage = $event->packages->contains(fn($p) => $p->isActive());
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
                                }" class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700/80 dark:bg-zinc-900/60">
                                    <div class="border-b border-zinc-200/80 bg-gradient-to-r from-orange-500/5 via-transparent to-transparent px-5 py-6 sm:px-8 sm:py-7 dark:border-zinc-700/80 dark:from-orange-500/10">
                                        <div class="flex flex-wrap items-start justify-between gap-4">
                                            <div>
                                                <span class="inline-flex items-center rounded-full bg-orange-500/15 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-orange-600 dark:text-orange-400">{{ __('Pendaftaran') }}</span>
                                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Registration') }}</h2>
                                                <p class="mt-1.5 max-w-xl text-sm text-zinc-500 dark:text-zinc-400">
                                                    @if ($event->isEffectiveOpenRegist())
                                                        {{ __('Registration open until :date', ['date' => $event->registration_closes_at?->format('d F Y H:i') ?? '—']) }}
                                                    @elseif ($event->isEffectivePublished())
                                                        {{ __('Early registration') }} — {{ __('Registration opens on :date', ['date' => $event->registration_opens_at?->format('d F Y H:i') ?? '—']) }}
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
                                            <flux:badge variant="solid"
                                                color="{{ $event->isEffectiveOpenRegist() ? 'green' : ($event->isEffectiveDone() || $event->isEffectiveClosedRegist() ? 'zinc' : 'blue') }}"
                                                size="sm">
                                                {{ $event->isEffectiveOpenRegist() ? __('Open') : ($event->isEffectivePublished() ? __('Early access') : $event->effective_status_label) }}
                                            </flux:badge>
                                        </div>

                                    </div>

                                    <div class="space-y-8 p-5 sm:p-6 lg:p-8">
                                    <div>
                                        <div class="flex items-start gap-2">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-orange-500/10 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                                                <flux:icon name="squares-2x2" class="size-4" />
                                            </span>
                                            <div>
                                                <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Select Bracket') }}</h3>
                                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih kategori bracket yang sesuai.') }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4 grid grid-cols-1 items-stretch gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
                                                <button type="button"
                                                    @if ($isFull) disabled @else x-on:click="selectedBracket = '{{ $bracket->id }}'; selectedBracketLabel = '{{ addslashes($bracket->name) }}'; if (requirePackage) scrollToPackages(); else scrollToForm()" @endif
                                                    class="registration-bracket-card h-full w-full"
                                                    :class="selectedBracket === '{{ $bracket->id }}'
                                                        ? 'border-orange-500 bg-orange-50/50 ring-2 ring-orange-500/20 dark:bg-orange-950/20'
                                                        : {{ $isFull ? "'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-75 dark:border-zinc-700 dark:bg-zinc-800/50'" : "'border-zinc-200 hover:border-orange-200 hover:shadow-md dark:border-zinc-700 dark:hover:border-orange-800/60'" }}">
                                                    @if ($isFull)
                                                        <span class="card-edge-badge card-edge-badge--full">{{ __('Full') }}</span>
                                                    @else
                                                        <span class="card-edge-badge card-edge-badge--available">{{ __('Available') }}</span>
                                                    @endif
                                                    <div class="registration-card-body">
                                                    <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $bracket->name }}</div>

                                                    <div class="mt-3 min-h-[2.75rem]">
                                                        @if ($showQuota)
                                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                                <div class="h-full rounded-full bg-orange-500 transition-all" style="width: {{ $progressPct }}%"></div>
                                                            </div>
                                                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                                {{ __(':used / :quota registered', ['used' => $registered, 'quota' => $quota]) }}
                                                            </p>
                                                        @elseif (!$bracket->hide_quota)
                                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Unlimited quota') }}</p>
                                                        @endif
                                                    </div>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    @if ($event->packages->isNotEmpty())
                                        <div id="registration-packages" x-cloak x-show="selectedBracket !== ''"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0 translate-y-2"
                                            x-transition:enter-end="opacity-100 translate-y-0">
                                            <div class="flex items-start gap-2">
                                                <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-orange-500/10 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                                                    <flux:icon name="gift" class="size-4" />
                                                </span>
                                                <div>
                                                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Select Package') }}</h3>
                                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pilih paket pendaftaran.') }}</p>
                                                </div>
                                            </div>
                                            <div class="mt-4 grid items-stretch gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                                @foreach ($event->packages as $package)
                                                    @php
                                                        $pkgFull = $package->isQuotaFull();
                                                        $pkgSelectable = $package->isActive() && !$pkgFull;
                                                        if (!$package->isActive()) {
                                                            $pkgEdgeBadgeClass = 'card-edge-badge--muted';
                                                            $pkgEdgeBadgeLabel = __('Coming soon');
                                                        } elseif ($pkgFull) {
                                                            $pkgEdgeBadgeClass = 'card-edge-badge--full';
                                                            $pkgEdgeBadgeLabel = $package->isSoldOut() ? __('Sold out') : __('Booked');
                                                        } else {
                                                            $pkgEdgeBadgeClass = 'card-edge-badge--available';
                                                            $pkgEdgeBadgeLabel = __('Available');
                                                        }
                                                    @endphp
                                                    <button type="button"
                                                        @if ($pkgSelectable) x-on:click="selectedPackage = '{{ $package->id }}'; selectedPackageLabel = '{{ addslashes($package->name) }}'; scrollToForm()" @endif
                                                        @if (!$pkgSelectable) disabled @endif
                                                        class="registration-bracket-card h-full w-full"
                                                        :class="selectedPackage === '{{ $package->id }}'
                                                            ? 'border-orange-500 bg-orange-50/50 ring-2 ring-orange-500/20 dark:bg-orange-950/20'
                                                            : {{ $pkgSelectable ? "'border-zinc-200 hover:border-orange-200 hover:shadow-md dark:border-zinc-700 dark:hover:border-orange-800/60'" : "'cursor-not-allowed border-zinc-200 bg-zinc-100 opacity-75 dark:border-zinc-700 dark:bg-zinc-800/50'" }}">
                                                        <span class="card-edge-badge {{ $pkgEdgeBadgeClass }}">{{ $pkgEdgeBadgeLabel }}</span>
                                                        <div class="registration-card-body">
                                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $package->name }}</div>
                                                        <p class="mt-2 text-lg font-bold text-orange-600 dark:text-orange-400">{{ $package->formatted_payable_amount }}</p>
                                                        @if ($package->hasAdminFee() && $package->adminFeeIsIncludedInPrice())
                                                            <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">
                                                                {{ __('Includes platform admin fee :fee', ['fee' => $package->formatted_admin_fee]) }}
                                                            </span>
                                                        @elseif ($package->hasAdminFee() && !$package->adminFeeIsIncludedInPrice())
                                                            <span class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">
                                                                {{ __('Registration :reg + admin :adm', ['reg' => $package->formatted_price, 'adm' => $package->formatted_admin_fee]) }}
                                                            </span>
                                                        @endif
                                                        @if (!$package->hide_quota && $package->quota !== null)
                                                            @php $rem = $package->remainingQuota(); @endphp
                                                            <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                                {{ $rem !== null ? __(':remaining of :quota slots', ['remaining' => $rem, 'quota' => $package->quota]) : __('Full') }}
                                                            </div>
                                                        @endif
                                                        @if ($package->rewards->isNotEmpty())
                                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                                @foreach ($package->rewards as $reward)
                                                                    <span class="inline-flex items-center gap-1 rounded-lg border border-zinc-200/80 bg-white px-2 py-0.5 text-xs text-zinc-600 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                                                        @if ($reward->icon && (str_starts_with($reward->icon, 'http') || str_starts_with($reward->icon, '/')))
                                                                            <img src="{{ $reward->icon }}"
                                                                                alt=""
                                                                                class="size-3.5 object-contain" />
                                                                        @elseif ($reward->icon)
                                                                            <flux:icon :name="$reward->icon"
                                                                                class="size-3.5 shrink-0" />
                                                                        @else
                                                                            <flux:icon name="gift"
                                                                                class="size-3.5 shrink-0" />
                                                                        @endif
                                                                        {{ $reward->name }}
                                                                    </span>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                        </div>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Jika bracket sudah dipilih tapi event tidak punya package: tampilkan pesan, jangan form --}}
                                    <div x-cloak x-show="selectedBracket !== '' && !requirePackage"
                                        class="mt-6 rounded-2xl border border-orange-200 bg-orange-50 p-4 text-center dark:border-orange-800 dark:bg-orange-950/30">
                                        <p class="text-sm font-medium text-orange-800 dark:text-orange-200">
                                            {{ __('Package Belum Tersedia') }}</p>
                                    </div>

                                    {{-- Form registrasi rider --}}
                                    <div id="registration-form" x-cloak
                                        x-show="selectedBracket !== '' && (requirePackage && selectedPackage !== '')"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-3"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="scroll-mt-6" style="display: none;">
                                        <div class="flex items-start gap-2">
                                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-orange-500/10 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                                                <flux:icon name="clipboard-document-check" class="size-4" />
                                            </span>
                                            <div>
                                                <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Data Registration') }}</h3>
                                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lengkapi data wali dan rider.') }}</p>
                                            </div>
                                        </div>

                                        <div class="mt-4">
                                            <form method="POST" action="{{ route('registrations.store', $event) }}"
                                                id="registration-form-submit" x-ref="regForm"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="package_id" x-bind:value="selectedPackage">
                                                <input type="hidden" name="bracket_id" x-bind:value="selectedBracket">
                                                <input type="hidden" name="use_rider_id" value="" id="input-use-rider-id">

                                                <div class="space-y-6">
                                                    @if ($errors->any())
                                                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                                                            <ul class="list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-300">
                                                                @foreach ($errors->all() as $err)
                                                                    <li>{{ $err }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif

                                                    <div class="registration-summary">
                                                        <span class="registration-summary-chip">
                                                            <flux:icon name="squares-2x2" variant="mini" class="size-3.5 text-orange-500" />
                                                            <span x-text="selectedBracketLabel || '—'"></span>
                                                        </span>
                                                        @if ($event->packages->isNotEmpty())
                                                            <span class="registration-summary-chip">
                                                                <flux:icon name="gift" variant="mini" class="size-3.5 text-orange-500" />
                                                                <span x-text="selectedPackageLabel || '—'"></span>
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="registration-form-block !bg-white dark:!bg-zinc-900/50">
                                                        <p class="registration-form-block-title">
                                                            <flux:icon name="user" class="size-4 text-orange-500" />
                                                            {{ __('Parent / Guardian') }}
                                                        </p>
                                                        <div class="grid gap-4 sm:grid-cols-2">
                                                            <flux:input name="parent_name" type="text"
                                                                :label="__('Parent / Guardian name')"
                                                                :value="old('parent_name')" required />
                                                            <flux:input name="whatsapp" type="tel"
                                                                :label="__('WhatsApp number')" :value="old('whatsapp')"
                                                                :placeholder="__('e.g. 08123456789')" required />
                                                        </div>
                                                    </div>

                                                    <div class="registration-form-block !bg-white dark:!bg-zinc-900/50">
                                                        <p class="registration-form-block-title">
                                                            <flux:icon name="identification" class="size-4 text-orange-500" />
                                                            {{ __('Rider data') }}
                                                        </p>
                                                        <div class="grid gap-4 sm:grid-cols-2">
                                                        <flux:input name="name" type="text"
                                                            :label="__('Full name')" :value="old('name')"
                                                            required />
                                                        <flux:input name="nickname" type="text"
                                                            :label="__('Nickname')" :value="old('nickname')"
                                                            required />
                                                        </div>
                                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                                        <flux:input name="pob" type="text"
                                                            :label="__('Place of birth')" :value="old('pob')"
                                                            required />
                                                        <flux:input name="dob" type="date"
                                                            :label="__('Date of birth')" :value="old('dob')"
                                                            required />
                                                        </div>
                                                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                                        <flux:select name="gender" :placeholder="__('— Select —')"
                                                            :label="__('Gender')" required>
                                                            <option value="boys" @selected(old('gender') === 'boys')>
                                                                {{ __('Boys') }}</option>
                                                            <option value="girls" @selected(old('gender') === 'girls')>
                                                                {{ __('Girls') }}</option>
                                                        </flux:select>
                                                        <flux:input name="number_plate" type="text"
                                                            :label="__('Number plate')" :value="old('number_plate')"
                                                            required />
                                                        </div>
                                                    {{-- Jersey size --}}
                                                    <div x-cloak
                                                        x-show="packageIdsWithJersey.includes(selectedPackage)"
                                                        class="mt-4 space-y-2" x-data="{ sizeChartPreviewOpen: false }"
                                                        @keydown.escape.window="sizeChartPreviewOpen = false">
                                                        <flux:select name="jersey_size"
                                                            :placeholder="__('— Select size —')"
                                                            :label="__('Jersey size')"
                                                            x-bind:required="packageIdsWithJersey.includes(selectedPackage)">
                                                            <option value="S" @selected(old('jersey_size') === 'S')>S
                                                            </option>
                                                            <option value="M" @selected(old('jersey_size') === 'M')>M
                                                            </option>
                                                            <option value="L" @selected(old('jersey_size') === 'L')>L
                                                            </option>
                                                            <option value="XL" @selected(old('jersey_size') === 'XL')>XL
                                                            </option>
                                                        </flux:select>
                                                        @if ($event->sizeChartUrl())
                                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                                                <button type="button"
                                                                    @click="sizeChartPreviewOpen = true"
                                                                    class="underline hover:text-zinc-700 dark:hover:text-zinc-300">
                                                                    {{ __('View size chart') }}
                                                                </button>
                                                            </p>
                                                            <div x-show="sizeChartPreviewOpen" x-transition.opacity
                                                                x-cloak
                                                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                                                @click.self="sizeChartPreviewOpen = false"
                                                                role="dialog" aria-modal="true"
                                                                :aria-hidden="!sizeChartPreviewOpen">
                                                                <img src="{{ $event->sizeChartUrl() }}"
                                                                    alt="{{ __('Size chart') }}"
                                                                    class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                                    @click.stop />
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="mt-4">
                                                        <livewire:team-pillbox-field />
                                                    </div>

                                                    <div class="mt-4 rounded-xl border border-dashed border-zinc-300 bg-zinc-50/80 p-4 dark:border-zinc-600 dark:bg-zinc-800/30">
                                                        <flux:label class="mb-2 block">{{ __('Photo KIA (Kartu Identitas Anak)') }}</flux:label>
                                                        <input type="file" name="photo_kia" id="photo_kia"
                                                            accept="image/jpeg,image/png,image/webp"
                                                            class="block w-full cursor-pointer text-sm text-zinc-500 file:mr-4 file:cursor-pointer file:rounded-lg file:border-0 file:bg-orange-500 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-orange-400 dark:file:bg-orange-600"
                                                            required />
                                                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                                            {{ __('JPG, PNG, WebP up to :max KB', ['max' => config('media.max_upload_size_kb', 2048)]) }}
                                                        </p>
                                                        @error('photo_kia')
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    </div>

                                                    <div class="pt-2">
                                                        <flux:button type="submit" variant="primary" icon="paper-airplane"
                                                            class="w-full justify-center !bg-orange-500 hover:!bg-orange-400 sm:w-auto"
                                                            x-bind:disabled="selectedBracket === '' || (requirePackage && selectedPackage === '')">
                                                            {{ __('Submit registration') }}
                                                        </flux:button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    @if ($showDuplicateRiderModal && count($similarRidersList) > 0)
                                        <div x-data x-init="$nextTick(() => $dispatch('modal-show', { name: 'duplicate-rider-modal' }))"></div>
                                        <flux:modal name="duplicate-rider-modal" focusable class="max-w-2xl"
                                            dismissible>
                                            <div class="space-y-4">
                                                <div>
                                                    <flux:heading size="lg">
                                                        {{ __('You may already be registered') }}
                                                    </flux:heading>
                                                    <flux:subheading class="mt-1">
                                                        {{ __('We found a rider with similar data for this WhatsApp number. Compare below and use the existing profile to continue.') }}
                                                    </flux:subheading>
                                                </div>
                                                <ul class="space-y-4">
                                                    @foreach ($similarRidersList as $sr)
                                                        <li
                                                            class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                                                            <div
                                                                class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                                                <span
                                                                    class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-900/30 px-2.5 py-0.5 text-sm font-medium text-amber-800 dark:text-amber-200">
                                                                    {{ __('Similarity') }}: {{ $sr['score'] }}%
                                                                </span>
                                                                <flux:button type="button" variant="primary"
                                                                    size="sm"
                                                                    x-on:click="document.getElementById('input-use-rider-id').value = '{{ $sr['id'] }}'; document.getElementById('registration-form-submit').submit()">
                                                                    {{ __('Use this profile') }}
                                                                </flux:button>
                                                            </div>
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                                                <div
                                                                    class="rounded-lg bg-blue-50/50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 p-3">
                                                                    <div
                                                                        class="font-medium text-blue-800 dark:text-blue-200 mb-2">
                                                                        {{ __('New data (from form)') }}</div>
                                                                    <dl
                                                                        class="space-y-1.5 text-zinc-700 dark:text-zinc-300">
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
                                                                    <div
                                                                        class="font-medium text-zinc-800 dark:text-zinc-200 mb-2">
                                                                        {{ __('Existing profile') }}</div>
                                                                    <dl
                                                                        class="space-y-1.5 text-zinc-700 dark:text-zinc-300">
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
                                </div>
                            @elseif (!$event->isRegistrationOpen() && $event->registration_opens_at)
                                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700/80 dark:bg-zinc-900/60">
                                    <div class="border-b border-zinc-200/80 bg-gradient-to-r from-orange-500/5 via-transparent to-transparent px-5 py-6 sm:px-8 dark:border-zinc-700/80 dark:from-orange-500/10">
                                        <span class="inline-flex items-center rounded-full bg-orange-500/15 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-orange-600 dark:text-orange-400">{{ __('Pendaftaran') }}</span>
                                        <h2 class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Registration') }}</h2>
                                    </div>
                                    <div class="space-y-4 p-5 sm:p-6 lg:p-8">
                                    @if (session('error'))
                                        <div
                                            class="rounded-xl border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3 text-sm text-red-700 dark:text-red-300">
                                            {{ session('error') }}</div>
                                    @endif
                                    <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 dark:border-orange-800 dark:bg-orange-950/30"
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
                                        <p class="text-sm text-orange-800 dark:text-orange-200">
                                            @if (now()->lt($event->registration_opens_at))
                                                {{ __('Registration opens on :date', ['date' => $event->registration_opens_at->format('d F Y H:i')]) }}
                                            @else
                                                {{ __('Registration is closed.') }}
                                            @endif
                                        </p>
                                        @if (now()->lt($event->registration_opens_at))
                                            <p class="mt-2 font-mono text-lg font-semibold text-orange-900 dark:text-orange-100"
                                                x-show="target > now">
                                                <span
                                                    x-text="String(diff.days).padStart(2,'0') + 'd ' + String(diff.hours).padStart(2,'0') + 'h ' + String(diff.minutes).padStart(2,'0') + 'm ' + String(diff.seconds).padStart(2,'0') + 's'"></span>
                                            </p>
                                        @endif
                                    </div>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ __('Have an early access code?') }}
                                    </p>
                                    <flux:button type="button" variant="primary" size="sm" x-data
                                        x-on:click="$dispatch('modal-show', { name: 'early-access-modal' })">
                                        {{ __('Early Registration') }}
                                    </flux:button>
                                    </div>
                                </div>
                            @else
                                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700/80 dark:bg-zinc-900/60">
                                    <div class="border-b border-zinc-200/80 bg-gradient-to-r from-zinc-500/5 via-transparent to-transparent px-5 py-6 sm:px-8 dark:border-zinc-700/80">
                                        <span class="inline-flex items-center rounded-full bg-zinc-500/15 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-zinc-400">{{ __('Pendaftaran') }}</span>
                                        <h2 class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Registration') }}</h2>
                                    </div>
                                    <div class="space-y-4 p-5 sm:p-6 lg:p-8">
                                    @if (session('error'))
                                        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                                            {{ session('error') }}</div>
                                    @endif
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ __('Registration is not open for this event.') }}</p>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ __('Have an early access code?') }}
                                    </p>
                                    <flux:button type="button" variant="primary" size="sm" x-data
                                        x-on:click="$dispatch('modal-show', { name: 'early-access-modal' })">
                                        {{ __('Early Registration') }}
                                    </flux:button>
                                    </div>
                                </div>
                            @endif
                        </flux:tab.panel>

                        @if ($showParticipantsPublicly)
                            <flux:tab.panel name="participant" :selected="$participantTabActive">
                                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700/80 dark:bg-zinc-900/60" x-data="{ search: '', selectedBracket: '' }">
                                    <div class="border-b border-zinc-200/80 px-5 py-6 sm:px-8 dark:border-zinc-700/80">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <span
                                                    class="inline-flex items-center rounded-full bg-zinc-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:bg-zinc-400/15 dark:text-zinc-300">{{ __('Peserta') }}</span>
                                                <h2 class="mt-2 text-xl font-bold text-zinc-900 dark:text-white">
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

                                    <div id="event-participants"
                                        class="overflow-x-auto border-t border-zinc-200 dark:border-zinc-700">
                                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                                            <thead class="bg-zinc-50/80 dark:bg-zinc-800/80">
                                                <tr>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Rider') }}</th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Team') }}</th>
                                                    <th
                                                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Bracket') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody
                                                class="divide-y divide-zinc-200 bg-white/50 dark:divide-zinc-700 dark:bg-transparent">
                                                @forelse ($participantRegistrations as $participantRegistration)
                                                    @php
                                                        $participantSearchText = mb_strtolower(
                                                            trim(
                                                                implode(' ', [
                                                                    (string) ($participantRegistration->rider?->name ??
                                                                        ''),
                                                                    (string) ($participantRegistration->rider
                                                                        ?->nickname ?? ''),
                                                                    (string) ($participantRegistration->number_plate ??
                                                                        ''),
                                                                    (string) ($participantRegistration->rider
                                                                        ?->number_plate ?? ''),
                                                                    (string) ($participantRegistration->rider?->teams
                                                                        ->pluck('name')
                                                                        ->implode(' ') ?? ''),
                                                                ]),
                                                            ),
                                                        );
                                                    @endphp
                                                    <tr
                                                        x-show="(selectedBracket === '' || selectedBracket === '{{ (string) $participantRegistration->bracket_id }}') && (search.trim() === '' || '{{ addslashes($participantSearchText) }}'.includes(search.trim().toLowerCase()))">
                                                        <td
                                                            class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                            {{ $participantRegistration->rider?->name ?? '—' }}
                                                            <span class="text-zinc-500 dark:text-zinc-400 block">
                                                                {{ $participantRegistration->rider?->nickname ?? '—' }}
                                                                ({{ $participantRegistration->rider?->number_plate ?? '—' }})
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
                                                        <td colspan="3"
                                                            class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
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

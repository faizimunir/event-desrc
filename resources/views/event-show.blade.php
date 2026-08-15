@extends('layouts.bento-public')

@section('title')
    {{ $event->title }}
@endsection

@push('head')
    <style>
        html {
            scroll-behavior: smooth;
        }

        #registration-form,
        #registration-packages,
        #event-participants {
            scroll-margin-top: 7rem;
        }
    </style>
@endpush

@section('content')
    <div class="overflow-x-hidden pb-6 lg:pb-8">
                <div
                    class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[300px_minmax(0,1fr)] lg:gap-x-8 lg:gap-y-8">
                    {{-- 1: Left sidebar (poster + team) --}}
                    <div class="space-y-4 lg:col-start-1 lg:row-start-1">
                        {{-- Poster --}}
                        <div class="w-full lg:min-w-0 lg:h-fit">
                            @if ($event->posterUrl())
                                <div
                                    x-data="{ expanded: false }"
                                    x-on:resize.window="if (window.innerWidth >= 1024) expanded = false"
                                    class="relative w-full overflow-hidden rounded-3xl border border-zinc-200/70 bg-zinc-900 shadow-[0_4px_24px_rgba(0,0,0,0.06)] dark:border-zinc-700/70 dark:shadow-black/20 lg:sticky lg:top-28"
                                >
                                    <button
                                        type="button"
                                        class="group relative block w-full text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 lg:pointer-events-none"
                                        x-on:click="if (window.matchMedia('(min-width: 1024px)').matches) return; expanded = !expanded"
                                        x-bind:aria-expanded="expanded.toString()"
                                        aria-label="{{ __('Toggle event poster') }}"
                                    >
                                        <div
                                            class="event-poster-frame overflow-hidden"
                                            x-bind:class="expanded && 'event-poster-frame--expanded'"
                                        >
                                            <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}" class="event-poster-image" />
                                        </div>

                                        <div
                                            x-show="!expanded"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            class="pointer-events-none absolute inset-x-0 bottom-0 flex items-end justify-center bg-gradient-to-t from-black/60 via-black/20 to-transparent pb-3 pt-10 lg:hidden"
                                        >
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-zinc-800 shadow-sm backdrop-blur-sm dark:bg-zinc-900/90 dark:text-zinc-100">
                                                <flux:icon name="chevron-down" variant="mini" class="size-3.5" />
                                                {{ __('Tap to expand') }}
                                            </span>
                                        </div>

                                        <div
                                            x-show="expanded"
                                            x-transition:leave="transition ease-in duration-200"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            class="pointer-events-none absolute inset-x-0 bottom-0 flex items-end justify-center bg-gradient-to-t from-black/50 to-transparent pb-3 pt-8 lg:hidden"
                                        >
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-zinc-800 shadow-sm backdrop-blur-sm dark:bg-zinc-900/90 dark:text-zinc-100">
                                                <flux:icon name="chevron-up" variant="mini" class="size-3.5" />
                                                {{ __('Tap to collapse') }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            @else
                                <div
                                    class="flex min-h-[40vh] w-full items-center justify-center rounded-3xl border border-zinc-200/70 bg-zinc-200 text-zinc-500 shadow-[0_4px_24px_rgba(0,0,0,0.06)] dark:border-zinc-700/70 dark:bg-zinc-800 dark:shadow-black/20 lg:min-h-0 lg:aspect-[3/4]">
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
                                        <template x-teleport="body">
                                            <div x-show="previewOpen" x-transition.opacity x-cloak
                                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                                @click.self="previewOpen = false" role="dialog" aria-modal="true"
                                                :aria-hidden="!previewOpen">
                                                <img src="{{ $rc->photo_rc_url }}" alt="{{ $rc->name }}"
                                                    class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                    @click.stop />
                                            </div>
                                        </template>
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
                                        <template x-teleport="body">
                                            <div x-show="previewOpen" x-transition.opacity x-cloak
                                                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                                @click.self="previewOpen = false" role="dialog" aria-modal="true"
                                                :aria-hidden="!previewOpen">
                                                <img src="{{ $mc->avatar_mc_url }}" alt="{{ $mc->name }}"
                                                    class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                    @click.stop />
                                            </div>
                                        </template>
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
                                <div class="flex flex-wrap items-center gap-2">
                                    @include('partials.event-status-badge', ['event' => $event, 'solid' => true])
                                    <flux:badge color="zinc" size="sm">
                                        {{ $event->isCategoryUmur() ? __('Umur') : __('Tahun') }}
                                    </flux:badge>
                                </div>
                                @if ($event->has_live_result)
                                    <flux:button variant="filled" size="sm"
                                        href="{{ route('live-result.show', $event->slug) }}" wire:navigate icon="radio"
                                        class="!rounded-xl !bg-red-500 !text-white hover:!bg-red-500 focus:!ring-red-600 dark:!bg-red-600 dark:hover:!bg-red-500">
                                        {{ __('Live Result') }}
                                    </flux:button>
                                @endif
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
                                    <p class="mt-2 text-sm leading-relaxed text-zinc-700 whitespace-pre-line dark:text-zinc-300">{{ trim($event->description) }}</p>
                                </div>
                            @endif
                        </div>

                        @php
                            $registrationFlowVisible = ($event->isRegistrationOpen() || $hasEarlyAccess) && $event->brackets_sorted_for_display->isNotEmpty();
                        @endphp

                        @if (! $registrationFlowVisible)
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
                        @endif

                        @if ($event->rundowns->isNotEmpty())
                        <div class="event-section-card">
                            <h2 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Rundown') }}</h2>
                            <p class="mt-1 mb-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Susunan acara race pada hari event.') }}</p>
                            <x-event-rundown-table :rundowns="$event->rundowns" />
                        </div>
                        @endif

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
                                                <template x-teleport="body">
                                                    <div x-show="previewOpen" x-transition.opacity x-cloak
                                                        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 p-4"
                                                        @click.self="previewOpen = false" role="dialog"
                                                        aria-modal="true" :aria-hidden="!previewOpen">
                                                        <img src="{{ $track->photoTrackUrl() }}"
                                                            alt="{{ $track->name }}"
                                                            class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-xl"
                                                            @click.stop />
                                                    </div>
                                                </template>
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
                    @endphp

                    @if ($showParticipantsPublicly)
                        <div x-data="{ tab: @js($participantTabActive ? 'participant' : 'registration') }">
                            <div class="bento-tabs mb-4 sm:mb-5" role="tablist" aria-label="{{ __('Event sections') }}">
                                <div
                                    class="bento-tabs__indicator"
                                    x-bind:class="tab === 'participant' && 'bento-tabs__indicator--end'"
                                    aria-hidden="true"
                                ></div>
                                <button
                                    type="button"
                                    role="tab"
                                    class="bento-tabs__item"
                                    x-on:click="tab = 'registration'"
                                    x-bind:aria-selected="(tab === 'registration').toString()"
                                    x-bind:class="tab === 'registration' && 'bento-tabs__item--active'"
                                >
                                    <flux:icon name="pencil-square" variant="mini" class="size-4 shrink-0" />
                                    {{ __('Registration') }}
                                </button>
                                <button
                                    type="button"
                                    role="tab"
                                    class="bento-tabs__item"
                                    x-on:click="tab = 'participant'"
                                    x-bind:aria-selected="(tab === 'participant').toString()"
                                    x-bind:class="tab === 'participant' && 'bento-tabs__item--active'"
                                >
                                    <flux:icon name="users" variant="mini" class="size-4 shrink-0" />
                                    {{ __('Participant') }}
                                </button>
                            </div>

                            <div
                                x-show="tab === 'registration'"
                                x-cloak
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-x-2"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-x-0"
                                x-transition:leave-end="opacity-0 -translate-x-2"
                                role="tabpanel"
                            >
                    @else
                        <div>
                    @endif
                            @if (($event->isRegistrationOpen() || $hasEarlyAccess) && $event->brackets_sorted_for_display->isNotEmpty())
                                @php
                                    $packageIdsWithJersey = $event->packages
                                        ->filter(fn($p) => $p->hasJerseyReward())
                                        ->pluck('id')
                                        ->values()
                                        ->all();
                                    $hasActivePackage = $event->packages->contains(fn($p) => $p->isActive());
                                @endphp

                                <div x-data="{
                                    selectedPackage: '',
                                    selectedPackageLabel: '',
                                    selectedBracket: '',
                                    selectedBracketLabel: '',
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
                                    <div class="border-b border-zinc-200/80 bg-gradient-to-r from-orange-500/5 via-transparent to-transparent px-4 py-5 sm:px-6 sm:py-6 dark:border-zinc-700/80 dark:from-orange-500/10">
                                        <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Registration') }}</h2>
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

                                    <div class="space-y-6 p-4 sm:p-5">
                                    <div>
                                        <div class="flex items-start gap-2">
                                            <span class="registration-step-icon">
                                                <flux:icon name="squares-2x2" class="size-5" />
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
                                                    @if ($isFull) disabled @else x-on:click="selectedBracket = '{{ $bracket->id }}'; selectedBracketLabel = '{{ addslashes($bracket->name) }}'; $dispatch('registration-bracket-selected', { id: {{ $bracket->id }} }); if (requirePackage) scrollToPackages(); else scrollToForm()" @endif
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
                                                <span class="registration-step-icon">
                                                    <flux:icon name="gift" class="size-5" />
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
                                                        @if ($pkgSelectable) x-on:click="selectedPackage = '{{ $package->id }}'; selectedPackageLabel = '{{ addslashes($package->name) }}'; $dispatch('registration-package-selected', { id: {{ $package->id }} }); scrollToForm()" @endif
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
                                                                                class="size-4 object-contain" />
                                                                        @elseif ($reward->icon)
                                                                            <flux:icon :name="$reward->icon"
                                                                                class="size-4 shrink-0" />
                                                                        @else
                                                                            <flux:icon name="gift"
                                                                                class="size-4 shrink-0" />
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

                                    {{-- Form registrasi rider (Livewire — no page reload on error) --}}
                                    <div id="registration-form" x-cloak
                                        x-show="selectedBracket !== '' && (requirePackage && selectedPackage !== '')"
                                        x-transition:enter="transition ease-out duration-300"
                                        x-transition:enter-start="opacity-0 translate-y-3"
                                        x-transition:enter-end="opacity-100 translate-y-0"
                                        class="scroll-mt-6 space-y-4" style="display: none;">
                                        <div class="flex items-start gap-3">
                                            <span class="registration-step-icon">
                                                <flux:icon name="clipboard-document-check" class="size-5" />
                                            </span>
                                            <div>
                                                <h3 class="text-base font-bold text-zinc-900 dark:text-white">{{ __('Data Registration') }}</h3>
                                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Lengkapi data wali dan rider.') }}</p>
                                            </div>
                                        </div>

                                        <livewire:registrations.event-registration-form
                                            :event="$event"
                                            :package-ids-with-jersey="$packageIdsWithJersey"
                                            :key="'event-reg-'.$event->id"
                                        />
                                    </div>
                                    </div>
                                </div>
                            @elseif (!$event->isRegistrationOpen() && $event->registration_opens_at)
                                <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-700/80 dark:bg-zinc-900/60">
                                    <div class="border-b border-zinc-200/80 bg-gradient-to-r from-orange-500/5 via-transparent to-transparent px-5 py-6 sm:px-8 dark:border-zinc-700/80 dark:from-orange-500/10">
                                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Registration') }}</h2>
                                    </div>
                                    <div class="space-y-4 p-5 sm:p-6 lg:p-8">
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
                                        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Registration') }}</h2>
                                    </div>
                                    <div class="space-y-4 p-5 sm:p-6 lg:p-8">
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
                    @if ($showParticipantsPublicly)
                            </div>

                            <div
                                x-show="tab === 'participant'"
                                x-cloak
                                x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-x-2"
                                x-transition:enter-end="opacity-100 translate-x-0"
                                x-transition:leave="transition ease-in duration-200"
                                x-transition:leave-start="opacity-100 translate-x-0"
                                x-transition:leave-end="opacity-0 -translate-x-2"
                                role="tabpanel"
                            >
                                <livewire:event-participant-list :event="$event" />
                            </div>
                        </div>
                    @else
                        </div>
                    @endif

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
@endsection

@push('scripts')
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
                    var offset = 112;
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
@endpush

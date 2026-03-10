<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">
    @include('partials.navbar')

    <main>
        {{-- Hero Section --}}
        <section class="relative border-b border-zinc-200/80 dark:border-zinc-700/80 overflow-hidden bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('hero.webp') }}');">
            <div class="absolute inset-0 bg-black/20 dark:bg-black/50" aria-hidden="true"></div>
            <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-24">
                <div class="max-w-2xl scroll-reveal" x-data x-intersect.once="$el.classList.add('in-view')">
                    <p class="text-sm font-medium text-zinc-300 uppercase tracking-wider">
                        {{ __('Platform Event Pushbike') }}
                    </p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl lg:text-[2.5rem] drop-shadow-sm">
                        {{ __('Temukan event balap terbaik') }}
                    </h1>
                    <p class="mt-4 text-lg leading-relaxed text-zinc-200">
                        {{ __('Daftar lomba, pilih bracket, dan ikuti event favorit Anda.') }}
                    </p>
                    <div class="mt-8">
                        <a href="#events" class="inline-flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-medium text-zinc-900 shadow-sm transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-black/20">
                            {{ __('Lihat events') }}
                            <flux:icon name="arrow-down" variant="mini" class="size-4" />
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- Events Section --}}
        <section id="events" class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20 scroll-mt-6">
            <header class="mb-8 sm:mb-10 scroll-reveal" x-data x-intersect.once="$el.classList.add('in-view')">
                <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                    {{ __('Events') }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Event yang sedang berlangsung dan akan datang.') }}
                </p>
            </header>

            @if(isset($events) && $events->isNotEmpty())
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 scroll-reveal-stagger" x-data x-intersect.once="$el.classList.add('in-view')">
                    @foreach($events as $event)
                        <a href="{{ route('events.public.show', $event) }}" wire:navigate class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 focus-visible:ring-offset-2 rounded-xl">
                            <article class="h-full overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition dark:border-zinc-700 dark:bg-zinc-800/50 group-hover:border-zinc-300 group-hover:shadow-md dark:group-hover:border-zinc-600">
                                <div class="relative aspect-video w-full overflow-hidden bg-zinc-100 dark:bg-zinc-700">
                                    @if($event->posterUrl())
                                        <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" />
                                    @else
                                        <div class="flex h-full items-center justify-center text-zinc-400">
                                            <flux:icon name="calendar" class="size-12" />
                                        </div>
                                    @endif
                                    <div class="absolute bottom-2 right-2">
                                        <flux:badge variant="solid" color="{{ $event->isEffectiveOpenRegist() ? 'green' : ($event->isEffectiveDone() ? 'zinc' : 'blue') }}" size="sm">{{ $event->effective_status_label }}</flux:badge>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <h3 class="font-semibold text-zinc-900 dark:text-white line-clamp-2 group-hover:text-zinc-700 dark:group-hover:text-zinc-200">
                                        {{ $event->title }}
                                    </h3>
                                    <p class="mt-2 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                        <flux:icon name="calendar-days" class="size-4 shrink-0" />
                                        {{ $event->start_at->format('d M Y, H:i') }}
                                    </p>
                                    @if($event->location)
                                        <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                            <flux:icon name="map-pin" class="size-4 shrink-0" />
                                            {{ $event->location->name }}
                                        </p>
                                    @endif
                                    <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white">
                                        {{ __('Lihat detail') }}
                                        <flux:icon name="arrow-right" variant="mini" class="size-4 transition group-hover:translate-x-0.5" />
                                    </span>
                                </div>
                            </article>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 py-16 text-center dark:border-zinc-700 dark:bg-zinc-800/30 scroll-reveal" x-data x-intersect.once="$el.classList.add('in-view')">
                    <flux:icon name="calendar" class="mx-auto size-12 text-zinc-400" />
                    <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada event') }}</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cek kembali nanti untuk event terbaru.') }}</p>
                </div>
            @endif
        </section>

        @include('partials.footer')
    </main>

    @fluxScripts
</body>
</html>

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

        <livewire:live-result-accordion-section section-id="live-result" />

        <livewire:event-cards-section :limit="12" section-id="events" :animate="true" />

        @include('partials.footer')
    </main>

    @fluxScripts
</body>
</html>

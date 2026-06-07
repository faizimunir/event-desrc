<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    @include('partials.head')
</head>
<body class="bento-page">
    <div class="bento-shell">
        @include('partials.navbar-bento')

        <main class="mt-4 space-y-4 sm:space-y-5">
            {{-- Hero --}}
            <section class="bento-card bento-hero relative overflow-hidden">
                <div
                    class="absolute inset-0 bg-cover bg-center bg-no-repeat"
                    style="background-image: url('{{ asset('hero.webp') }}');"
                    aria-hidden="true"
                ></div>
                <div class="absolute inset-0 bg-gradient-to-br from-zinc-950/90 via-zinc-950/70 to-zinc-950/50 dark:from-zinc-950/95 dark:via-zinc-950/80 dark:to-zinc-950/60" aria-hidden="true"></div>
                <div class="hero-glow hero-glow--orange absolute -top-32 right-0 h-[28rem] w-[28rem] opacity-60" aria-hidden="true"></div>
                <div class="hero-glow hero-glow--accent absolute -bottom-40 left-1/4 h-80 w-80 opacity-40" aria-hidden="true"></div>
                <div class="absolute inset-0 bg-grid-pattern opacity-[0.15]" aria-hidden="true"></div>

                <div class="relative z-10 px-6 pb-14 pt-10 sm:px-10 sm:pb-16 sm:pt-12 lg:px-12">
                    <div class="max-w-3xl scroll-reveal" x-data x-intersect.once="$el.classList.add('in-view')">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3.5 py-1.5 text-xs font-medium uppercase tracking-wider text-white/90 backdrop-blur-md">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex size-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-orange-500"></span>
                            </span>
                            {{ __('Platform Event Pushbike') }}
                        </span>

                        <h1 class="mt-4 text-4xl font-bold tracking-tight text-white sm:mt-5 sm:text-5xl lg:text-[3.5rem] lg:leading-[1.1]">
                            {{ __('Temukan event') }}
                            <span class="bg-gradient-to-r from-orange-400 to-amber-300 bg-clip-text text-transparent">{{ __('balap terbaik') }}</span>
                        </h1>

                        <p class="mt-5 max-w-xl text-lg leading-relaxed text-zinc-300 sm:text-xl">
                            {{ __('Daftar lomba, pilih bracket, dan ikuti event favorit Anda — semua dalam satu platform.') }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3 sm:mt-9 sm:gap-4">
                            <a
                                href="#events"
                                class="inline-flex items-center justify-center gap-2 rounded-full bg-orange-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-orange-500/25 transition hover:bg-orange-400 focus:outline-none focus:ring-2 focus:ring-orange-400 focus:ring-offset-2 focus:ring-offset-zinc-950"
                            >
                                {{ __('Lihat events') }}
                                <flux:icon name="arrow-down" variant="mini" class="size-4" />
                            </a>
                            <a
                                href="#live-result"
                                class="inline-flex items-center justify-center gap-2 rounded-full border border-white/20 bg-white/10 px-6 py-3 text-sm font-semibold text-white backdrop-blur-md transition hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-white/50 focus:ring-offset-2 focus:ring-offset-zinc-950"
                            >
                                <flux:icon name="radio" variant="mini" class="size-4" />
                                {{ __('Live Result') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="absolute bottom-6 left-1/2 z-10 hidden -translate-x-1/2 sm:block" aria-hidden="true">
                    <div class="flex flex-col items-center gap-2 text-white/50">
                        <span class="text-xs uppercase tracking-widest">{{ __('Scroll') }}</span>
                        <flux:icon name="chevron-down" variant="mini" class="size-4 animate-bounce" />
                    </div>
                </div>
            </section>

            <div class="bento-card bento-section-shell">
                <livewire:live-result-accordion-section section-id="live-result" :animate="true" />
            </div>

            <div class="bento-card bento-section-shell">
                <livewire:event-cards-section :limit="12" section-id="events" :animate="true" />
            </div>

            <div class="bento-card bento-footer">
                @include('partials.footer-bento')
            </div>
        </main>
    </div>

    @fluxScripts
</body>
</html>

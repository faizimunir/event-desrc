<section
    @if($sectionId) id="{{ $sectionId }}" @endif
    class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-14 sm:py-16 lg:py-20 @if($sectionId) scroll-mt-20 @endif"
>
    <header class="mb-8 sm:mb-10 @if($animate) scroll-reveal @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-red-600 dark:bg-red-500/15 dark:text-red-400">
                    <span class="relative flex size-1.5">
                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                        <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                    </span>
                    {{ __('Realtime') }}
                </span>
                <h2 class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                    {{ __('Live Result') }}
                </h2>
                <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Pilih event untuk melihat hasil live.') }}
                </p>
            </div>
            <a href="{{ route('live-result.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 transition hover:text-orange-500 dark:text-orange-400 dark:hover:text-orange-300">
                {{ __('Lihat semua') }}
                <flux:icon name="arrow-right" variant="mini" class="size-4" />
            </a>
        </div>
    </header>

    @if($events->isEmpty())
        <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/80 py-16 text-center dark:border-zinc-700 dark:bg-zinc-800/30 @if($animate) scroll-reveal @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="chart-bar" class="size-7 text-zinc-400 dark:text-zinc-500" />
            </div>
            <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada live result') }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cek kembali nanti untuk event yang tersedia.') }}</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 @if($animate) scroll-reveal-stagger @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            @foreach($events as $ev)
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:border-zinc-300 hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-800/60 dark:hover:border-zinc-600">
                    <div class="relative aspect-[16/9] overflow-hidden bg-zinc-100 dark:bg-zinc-700">
                        @if($ev->posterUrl())
                            <img src="{{ $ev->posterUrl() }}" alt="{{ $ev->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                        @else
                            <div class="flex h-full items-center justify-center text-zinc-400">
                                <flux:icon name="chart-bar" class="size-12" />
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        <div class="absolute left-3 top-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                                <span class="relative flex size-1.5">
                                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-white opacity-75"></span>
                                    <span class="relative inline-flex size-1.5 rounded-full bg-white"></span>
                                </span>
                                LIVE
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="font-semibold text-zinc-900 line-clamp-2 dark:text-white">{{ $ev->title }}</h3>
                        <div class="mt-3 space-y-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                            @if($ev->start_at)
                                <p class="flex items-center gap-1.5">
                                    <flux:icon name="calendar-days" class="size-4 shrink-0" />
                                    {{ $ev->start_at->format('d M Y, H:i') }}
                                </p>
                            @endif
                            @if($ev->location)
                                <p class="flex items-center gap-1.5">
                                    <flux:icon name="map-pin" class="size-4 shrink-0" />
                                    {{ $ev->location->name }}
                                </p>
                            @endif
                        </div>
                        <div class="mt-5 pt-2">
                            <flux:button variant="primary" size="sm" href="{{ route('live-result.show', $ev->slug) }}" wire:navigate class="w-full sm:w-auto">
                                {{ __('Buka live result') }}
                            </flux:button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

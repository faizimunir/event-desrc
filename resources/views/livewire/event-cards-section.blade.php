<section
    @if($sectionId) id="{{ $sectionId }}" @endif
    class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-14 sm:py-16 lg:py-20 @if($sectionId) scroll-mt-20 @endif"
>
    @if($showHeader)
        <header class="mb-8 sm:mb-10 @if($animate) scroll-reveal @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <span class="inline-flex items-center rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold uppercase tracking-wider text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                        {{ __('Event Terbaru') }}
                    </span>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                        {{ __('Events') }}
                    </h2>
                    <p class="mt-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Event yang sedang berlangsung dan akan datang.') }}
                    </p>
                </div>
                <a href="{{ route('events.public.index') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-orange-600 transition hover:text-orange-500 dark:text-orange-400 dark:hover:text-orange-300">
                    {{ __('Lihat semua') }}
                    <flux:icon name="arrow-right" variant="mini" class="size-4" />
                </a>
            </div>
        </header>
    @endif

    @if(isset($events) && $events->isNotEmpty())
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 lg:gap-6 @if($animate) scroll-reveal-stagger @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            @foreach($events as $event)
                <a href="{{ route('events.public.show', $event) }}" wire:navigate class="group block focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 rounded-2xl">
                    <article class="h-full overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition duration-300 dark:border-zinc-700 dark:bg-zinc-800/60 group-hover:-translate-y-1 group-hover:border-zinc-300 group-hover:shadow-xl dark:group-hover:border-zinc-600">
                        <div class="relative aspect-[4/3] w-full overflow-hidden bg-zinc-100 dark:bg-zinc-700">
                            @if($event->posterUrl())
                                <img src="{{ $event->posterUrl() }}" alt="{{ $event->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                            @else
                                <div class="flex h-full items-center justify-center text-zinc-400">
                                    <flux:icon name="calendar" class="size-12" />
                                </div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 transition duration-300 group-hover:opacity-100"></div>
                            <div class="absolute left-3 top-3">
                                <flux:badge variant="solid" color="{{ $event->isEffectiveOpenRegist() ? 'green' : ($event->isEffectiveLive() ? 'red' : ($event->isEffectiveDone() ? 'zinc' : 'blue')) }}" size="sm">{{ $event->effective_status_label }}</flux:badge>
                            </div>
                        </div>
                        <div class="p-5">
                            <h3 class="font-semibold text-zinc-900 dark:text-white line-clamp-2 transition group-hover:text-orange-600 dark:group-hover:text-orange-400">
                                {{ $event->title }}
                            </h3>
                            <p class="mt-2.5 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="calendar-days" class="size-4 shrink-0" />
                                {{ $event->start_at->format('d M Y, H:i') }}
                            </p>
                            @if($event->location)
                                <p class="mt-1 flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                    <flux:icon name="map-pin" class="size-4 shrink-0" />
                                    <span class="line-clamp-1">{{ $event->location->name }}</span>
                                </p>
                            @endif
                            <span class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-orange-600 dark:text-orange-400">
                                {{ __('Lihat detail') }}
                                <flux:icon name="arrow-right" variant="mini" class="size-4 transition group-hover:translate-x-1" />
                            </span>
                        </div>
                    </article>
                </a>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-zinc-200 bg-zinc-50/80 py-16 text-center dark:border-zinc-700 dark:bg-zinc-800/30 @if($animate) scroll-reveal @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="calendar" class="size-7 text-zinc-400" />
            </div>
            <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada event') }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cek kembali nanti untuk event terbaru.') }}</p>
        </div>
    @endif
</section>

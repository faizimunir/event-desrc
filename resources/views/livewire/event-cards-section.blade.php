<section
    @if($sectionId) id="{{ $sectionId }}" @endif
    class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20 @if($sectionId) scroll-mt-6 @endif"
>
    @if($showHeader)
        <header class="mb-8 sm:mb-10 @if($animate) scroll-reveal @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            <h2 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white sm:text-3xl">
                {{ __('Events') }}
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Event yang sedang berlangsung dan akan datang.') }}
            </p>
        </header>
    @endif

    @if(isset($events) && $events->isNotEmpty())
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 @if($animate) scroll-reveal-stagger @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
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
                                <flux:badge variant="solid" color="{{ $event->isEffectiveOpenRegist() ? 'green' : ($event->isEffectiveLive() ? 'red' : ($event->isEffectiveDone() ? 'zinc' : 'blue')) }}" size="sm">{{ $event->effective_status_label }}</flux:badge>
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
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/50 py-16 text-center dark:border-zinc-700 dark:bg-zinc-800/30 @if($animate) scroll-reveal @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            <flux:icon name="calendar" class="mx-auto size-12 text-zinc-400" />
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada event') }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cek kembali nanti untuk event terbaru.') }}</p>
        </div>
    @endif
</section>


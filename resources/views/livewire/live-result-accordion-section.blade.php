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
        <div class="flex flex-col gap-2 @if($animate) scroll-reveal-stagger @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            @foreach($events as $ev)
                @include('live-result.partials.event-list-item', ['event' => $ev])
            @endforeach
        </div>
    @endif
</section>

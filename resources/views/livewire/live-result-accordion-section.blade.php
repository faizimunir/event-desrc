<section
    @if($sectionId) id="{{ $sectionId }}" @endif
    class="bento-home-section @if($sectionId) scroll-mt-20 @endif"
>
    @include('partials.bento-section-header', [
        'variant' => 'live',
        'eyebrow' => __('Realtime'),
        'title' => __('Live Result'),
        'description' => __('Track race results in real-time.'),
        'animate' => $animate,
        'livePing' => false,
    ])

    @if($events->isEmpty())
        <div class="bento-empty-state !py-12 @if($animate) scroll-reveal @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="chart-bar" class="size-6 text-zinc-400 dark:text-zinc-500" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada live result') }}</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cek kembali nanti untuk event yang tersedia.') }}</p>
        </div>
    @else
        <div class="flex flex-col gap-2 @if($animate) scroll-reveal-stagger @endif" @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif>
            @foreach($events as $ev)
                @include('live-result.partials.event-list-item', ['event' => $ev])
            @endforeach
        </div>
    @endif

    @if($showViewAll)
        <div class="bento-section-footer">
            @include('partials.bento-section-cta', ['href' => route('live-result.index')])
        </div>
    @endif
</section>

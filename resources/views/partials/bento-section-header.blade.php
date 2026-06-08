@props([
    'variant' => 'orange',
    'eyebrow',
    'title',
    'description' => null,
    'animate' => false,
    'livePing' => false,
])

<header
    @class([
        'bento-section-header',
        'scroll-reveal' => $animate,
    ])
    @if($animate) x-data x-intersect.once="$el.classList.add('in-view')" @endif
>
    <div @class([
        'bento-section-header__title-wrap',
        'bento-section-header__title-wrap--live' => $variant === 'live',
        'bento-section-header__title-wrap--orange' => $variant === 'orange',
    ])>
        <span @class([
            'bento-section-header__eyebrow',
            'bento-section-header__eyebrow--live' => $variant === 'live',
            'bento-section-header__eyebrow--orange' => $variant === 'orange',
        ])>
            @if($livePing)
                <span class="relative flex size-1.5">
                    <span class="absolute inline-flex size-full animate-ping rounded-full bg-red-500 opacity-75"></span>
                    <span class="relative inline-flex size-1.5 rounded-full bg-red-500"></span>
                </span>
            @endif
            {{ $eyebrow }}
        </span>

        <h2 class="bento-section-header__title">{{ $title }}</h2>

        @if($description)
            <p class="bento-section-header__desc">{{ $description }}</p>
        @endif
    </div>
</header>

@props([
    'media' => null,
    'size' => 400,
    'alt' => '',
    'class' => '',
])

@if ($media)
    @php
        $variants = $media->meta['variants'] ?? [];
        $srcsetParts = [];
        foreach ($variants as $w) {
            $srcsetParts[] = $media->getUrl($w) . ' ' . $w . 'w';
        }
        $srcset = implode(', ', $srcsetParts);
        $sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, ' . $size . 'px';
    @endphp
    <img
        src="{{ $media->getUrl($size) }}"
        @if ($srcset !== '') srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
        alt="{{ $alt }}"
        loading="lazy"
        {{ $attributes->merge(['class' => $class]) }}
    />
@else
    <img
        src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300' viewBox='0 0 400 300'%3E%3Crect fill='%23e5e7eb' width='400' height='300'/%3E%3Ctext fill='%239ca3af' font-family='sans-serif' font-size='18' x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle'%3ENo image%3C/text%3E%3C/svg%3E"
        alt="{{ $alt }}"
        loading="lazy"
        {{ $attributes->merge(['class' => $class]) }}
    />
@endif

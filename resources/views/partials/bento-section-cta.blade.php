@props([
    'href',
    'label' => __('Lihat semua'),
])

<a
    href="{{ $href }}"
    wire:navigate
    {{ $attributes->merge(['class' => 'group inline-flex items-center gap-2.5 rounded-full bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white no-underline shadow-md shadow-orange-500/20']) }}
>
    {{ $label }}
    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-white/25 text-white transition-all duration-200 ease-out group-hover:translate-x-1 group-hover:bg-white group-hover:text-orange-500">
        <flux:icon name="arrow-right" variant="mini" class="size-3.5" />
    </span>
</a>

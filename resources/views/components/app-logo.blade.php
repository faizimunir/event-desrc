@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand name="Delta Eagle Sidoarjo" {{ $attributes }}>
        <x-slot name="logo">
            <img
                src="{{ asset('toogle-light.webp') }}"
                alt="DESRC"
                class="size-8 dark:hidden"
            >
            <img
                src="{{ asset('toogle-dark.webp') }}"
                alt="DESRC"
                class="size-8 hidden dark:block"
            >
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Delta Eagle Sidoarjo" {{ $attributes }}>
        <x-slot name="logo">
            <img
                src="{{ asset('toogle-light.webp') }}"
                alt="DESRC"
                class="size-8 dark:hidden"
            >
            <img
                src="{{ asset('toogle-dark.webp') }}"
                alt="DESRC"
                class="size-8 hidden dark:block"
            >
        </x-slot>
    </flux:brand>
@endif

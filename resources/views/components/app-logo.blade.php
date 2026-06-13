@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo">
            <img
                src="{{ asset('logo-light.webp') }}"
                alt="DESRC"
                class="h-8 w-auto dark:hidden"
            >
            <img
                src="{{ asset('logo-dark.webp') }}"
                alt="DESRC"
                class="h-8 w-auto hidden dark:block"
            >
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo">
            <img
                src="{{ asset('logo-light.webp') }}"
                alt="DESRC"
                class="h-8 w-auto dark:hidden"
            >
            <img
                src="{{ asset('logo-dark.webp') }}"
                alt="DESRC"
                class="h-8 w-auto hidden dark:block"
            >
        </x-slot>
    </flux:brand>
@endif

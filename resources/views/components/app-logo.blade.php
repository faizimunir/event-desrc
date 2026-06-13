@props([
    'sidebar' => false,
])

@if ($sidebar)
    <flux:sidebar.brand {{ $attributes }}>
        <x-slot name="logo">
            <img
                src="{{ asset('logo-light.webp') }}"
                alt="DESRC"
                class="dark:hidden"
            >
            <img
                src="{{ asset('logo-dark.webp') }}"
                alt="DESRC"
                class="hidden dark:block"
            >
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand {{ $attributes }}>
        <x-slot name="logo">
            <img
                src="{{ asset('logo-light.webp') }}"
                alt="DESRC"
                class="dark:hidden"
            >
            <img
                src="{{ asset('logo-dark.webp') }}"
                alt="DESRC"
                class="hidden dark:block"
            >
        </x-slot>
    </flux:brand>
@endif

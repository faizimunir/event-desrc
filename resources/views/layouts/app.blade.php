@props(['title' => null, 'unifiedHeader' => false])

<x-layouts::app.sidebar :title="$title" :unified-header="$unifiedHeader">
    <flux:main @class([
        'users-hero-main' => $unifiedHeader,
        '!px-4 lg:!px-4' => ! $unifiedHeader,
    ])>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="!px-1 lg:!px-1">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

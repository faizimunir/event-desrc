<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="!px-4 lg:!px-4">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>

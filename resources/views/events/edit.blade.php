<x-layouts::app :title="__('Edit Event')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:events.event-form :event="$event" />
    </div>
</x-layouts::app>

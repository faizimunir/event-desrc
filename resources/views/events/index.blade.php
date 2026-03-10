<x-layouts::app :title="__('Events')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Events Management') }}</flux:heading>
            @canAs('event.create')
                <flux:button variant="primary" :href="route('events.create')" wire:navigate icon="plus">
                    {{ __('Add Event') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:events.event-list />
    </div>
</x-layouts::app>

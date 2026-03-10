<x-layouts::app :title="__('Organizers')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Organizers Management') }}</flux:heading>
            @canAs('organizer.create')
                <flux:button variant="primary" :href="route('organizers.create')" wire:navigate icon="plus">
                    {{ __('Add Organizer') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:organizers.organizer-list />
    </div>
</x-layouts::app>

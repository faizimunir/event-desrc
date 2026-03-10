<x-layouts::app :title="__('Racing Committees')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Racing Committees') }}</flux:heading>
            @canAs('rc.create')
                <flux:button variant="primary" :href="route('racing-committees.create')" wire:navigate icon="plus">
                    {{ __('Add Racing Committee') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:racing-committees.racing-committee-list />
    </div>
</x-layouts::app>

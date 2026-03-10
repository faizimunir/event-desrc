<x-layouts::app :title="__('Levels')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Levels') }}</flux:heading>
            @canAs('level.create')
                <flux:button variant="primary" :href="route('levels.create')" wire:navigate icon="plus">
                    {{ __('Add Level') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:levels.level-list />
    </div>
</x-layouts::app>

<x-layouts::app :title="__('Master of Ceremonies')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Master of Ceremonies') }}</flux:heading>
            @canAs('mc.create')
                <flux:button variant="primary" :href="route('master-of-ceremonies.create')" wire:navigate icon="plus">
                    {{ __('Add MC') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:master-of-ceremonies.master-of-ceremony-list />
    </div>
</x-layouts::app>

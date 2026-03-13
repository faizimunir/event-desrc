<x-layouts::app :title="__('Teams')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Teams Management') }}</flux:heading>
            @canAs('team.create')
                <flux:button variant="primary" :href="route('teams.create')" wire:navigate icon="plus">
                    {{ __('Add Team') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:teams.team-list />
    </div>
</x-layouts::app>

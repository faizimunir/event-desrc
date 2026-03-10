<x-layouts::app :title="__('Permissions')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Permissions') }}</flux:heading>
            <flux:button variant="primary" :href="route('permissions.create')" wire:navigate icon="plus">
                {{ __('Add Permission') }}
            </flux:button>
        </div>

        <livewire:permissions.permission-list />
    </div>
</x-layouts::app>

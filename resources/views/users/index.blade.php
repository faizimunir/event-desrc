<x-layouts::app :title="__('Users')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Users Management') }}</flux:heading>
            @canAs('user.create')
                <flux:button variant="primary" :href="route('users.create')" wire:navigate icon="plus">
                    {{ __('Add User') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:users.user-list />
    </div>
</x-layouts::app>

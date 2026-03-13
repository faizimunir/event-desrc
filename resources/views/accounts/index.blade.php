<x-layouts::app :title="__('Accounts')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Accounts Management') }}</flux:heading>
            @canAs('account.create')
                <flux:button variant="primary" :href="route('accounts.create')" wire:navigate icon="plus">
                    {{ __('Add Account') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:accounts.account-list />
    </div>
</x-layouts::app>

<x-layouts::app :title="__('Rewards')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading>{{ __('Rewards Management') }}</flux:heading>
            @canAs('reward.create')
                <flux:button variant="primary" :href="route('rewards.create')" wire:navigate icon="plus">
                    {{ __('Add Reward') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:rewards.reward-list />
    </div>
</x-layouts::app>
